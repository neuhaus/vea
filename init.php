<?php
/**
 * vea - bootstrap.
 *
 * Loads configuration, the template engine and the database, and defines the
 * small helper API used by index.php and service.php.
 *
 * This file must stay PHP 5.5 compatible: no short closures, no scalar type
 * hints, no return types, no null-coalescing operator.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Do not advertise the PHP/interpreter version in responses.
if (!headers_sent()) {
    header_remove('X-Powered-By');
}

define('VEA_ROOT', __DIR__);

require VEA_ROOT . '/compat.php';
require VEA_ROOT . '/idna.php';
require VEA_ROOT . '/config.php';
require VEA_ROOT . '/smarty/libs/Smarty.class.php';
require VEA_ROOT . '/view.php';

/* ---------------------------------------------------------------------- */
/* HTTP helpers                                                           */
/* ---------------------------------------------------------------------- */

/**
 * True when the request reached us over TLS.
 *
 * X-Forwarded-* headers are only honoured when the peer is a configured
 * trusted proxy, because otherwise a client could spoof them.
 *
 * @return bool
 */
function vea_is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    if (vea_is_trusted_proxy()) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO']);
            if (strtolower(trim($proto[0])) === 'https') {
                return true;
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }
        if (!empty($_SERVER['HTTP_FRONT_END_HTTPS'])
            && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') {
            return true;
        }
    }
    return false;
}

/**
 * True when the request came from the loopback interface.
 *
 * REMOTE_ADDR is set by the web server from the TCP peer and cannot be
 * spoofed through HTTP headers.
 *
 * @return bool
 */
function vea_is_local()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if ($ip === '::1' || $ip === '::ffff:127.0.0.1') {
        return true;
    }
    return strpos($ip, '127.') === 0;
}

/**
 * True when the immediate peer is a configured trusted reverse proxy.
 *
 * @return bool
 */
function vea_is_trusted_proxy()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $proxies = isset($GLOBALS['TRUSTED_PROXIES']) ? $GLOBALS['TRUSTED_PROXIES'] : array();
    return is_array($proxies) && in_array($ip, $proxies, true);
}

/**
 * Refuse the request unless it is encrypted or local.
 *
 * @return void
 */
function require_secure_transport()
{
    if (vea_is_https() || vea_is_local()) {
        return;
    }
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    exit("403 Forbidden: vea must be reached over HTTPS or from localhost\n");
}

/**
 * True when the caller expects a JSON response (AJAX).
 *
 * @return bool
 */
function vea_wants_json()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    if (!empty($_SERVER['HTTP_ACCEPT'])
        && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    return false;
}

/**
 * Emit a JSON response and stop.
 *
 * @param  array $data
 * @return void
 */
function vea_json($data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Emit a JSON error response and stop.
 *
 * @param  string $message
 * @param  int    $status
 * @return void
 */
function vea_json_error($message, $status)
{
    $texts = array(
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
    );
    $text = isset($texts[$status]) ? $texts[$status] : 'Error';
    header('HTTP/1.1 ' . $status . ' ' . $text);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => $message));
    exit;
}

/* ---------------------------------------------------------------------- */
/* Session and CSRF protection                                            */
/* ---------------------------------------------------------------------- */

/**
 * Start the session used to hold the CSRF token. A session cookie is set with
 * HttpOnly (and SameSite where the PHP version supports it).
 *
 * @return void
 */
function vea_session()
{
    if (session_id() !== '') {
        return;
    }
    $secure = vea_is_https();

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ));
    } else {
        session_set_cookie_params(0, '/', '', $secure, true);
        @ini_set('session.cookie_samesite', 'Strict');
    }
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.cookie_httponly', '1');

    session_start();
}

/**
 * Return the per-session CSRF token, creating it if necessary.
 *
 * @return string
 */
function csrf_token()
{
    vea_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the CSRF token submitted with a state-changing request.
 *
 * @return void
 */
function csrf_check()
{
    vea_session();

    $expected = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
    $sent = '';
    if (isset($_POST['csrf'])) {
        $sent = (string) $_POST['csrf'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $sent = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        vea_json_error('invalid or missing CSRF token', 403);
    }
}

/* ---------------------------------------------------------------------- */
/* Authentication (HTTP Basic, performed by the web server)               */
/* ---------------------------------------------------------------------- */

/**
 * The username the web server authenticated, or an empty string.
 *
 * Only REMOTE_USER (and its post-internal-redirect variant) is trusted: the
 * web server sets it once authentication succeeded. PHP_AUTH_USER is not used,
 * because it can be populated from a client-supplied Authorization header
 * without any authentication having taken place.
 *
 * @return string
 */
function vea_httpd_user()
{
    foreach (array('REMOTE_USER', 'REDIRECT_REMOTE_USER') as $key) {
        if (!empty($_SERVER[$key])) {
            return (string) $_SERVER[$key];
        }
    }
    return '';
}

/**
 * Deny the request because there is no authenticated identity.
 *
 * @param  bool $forbidden force a 403 instead of a 401 challenge
 * @return void
 */
function vea_auth_deny($forbidden = false)
{
    $supplied = !empty($_SERVER['PHP_AUTH_USER'])
        || !empty($_SERVER['HTTP_AUTHORIZATION'])
        || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);

    if ($forbidden || $supplied) {
        // Either the identity was supplied but rejected (asking again would
        // loop), or the user is authenticated but not allowed.
        $status = 403;
        $text = 'Forbidden';
        $message = 'access denied';
    } else {
        $status = 401;
        $text = 'Unauthorized';
        $message = 'authentication required';
        header('WWW-Authenticate: Basic realm="' . AUTH_REALM . '"');
    }

    header('HTTP/1.1 ' . $status . ' ' . $text);
    if (vea_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => $message));
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $status . ' ' . $text . "\n";
    }
    exit;
}

/**
 * Require a web-server-authenticated user and return its name.
 *
 * @return string
 */
function require_auth()
{
    $user = vea_httpd_user();
    if ($user === '') {
        vea_auth_deny();
    }

    $allowed = isset($GLOBALS['AUTH_USERS']) ? $GLOBALS['AUTH_USERS'] : array();
    if (is_array($allowed) && count($allowed) > 0 && !in_array($user, $allowed, true)) {
        vea_auth_deny(true);
    }

    return $user;
}

/* ---------------------------------------------------------------------- */
/* Version                                                                */
/* ---------------------------------------------------------------------- */

/**
 * The application version.
 *
 * Only exposed to authenticated callers, so it never leaks to anonymous
 * requests (for example through the authentication challenge or an error
 * page).
 *
 * @return string
 */
function vea_version()
{
    if (vea_httpd_user() === '') {
        return '';
    }
    return '0.12';
}

/* ---------------------------------------------------------------------- */
/* Database                                                               */
/* ---------------------------------------------------------------------- */

/**
 * Shared PDO connection (lazy).
 *
 * @return PDO
 */
function vea_db()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log('vea: database connection failed: ' . $e->getMessage());
            if (vea_wants_json()) {
                vea_json_error('database unavailable', 500);
            }
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain; charset=utf-8');
            exit("500 Internal Server Error\n");
        }
    }
    return $pdo;
}

/**
 * Run a query with bound parameters and return all rows.
 *
 * @param  string $sql
 * @param  array  $params
 * @return array
 */
function db_query($sql, $params = array())
{
    $stmt = vea_db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    return $rows;
}

/**
 * Run a statement with bound parameters and return the affected row count.
 *
 * @param  string $sql
 * @param  array  $params
 * @return int
 */
function db_exec($sql, $params = array())
{
    $stmt = vea_db()->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    $stmt->closeCursor();
    return $count;
}

/* ---------------------------------------------------------------------- */
/* Mail password hashing (Dovecot-compatible)                             */
/* ---------------------------------------------------------------------- */

/**
 * Build a crypt()-style salt using the alphabet crypt() accepts.
 *
 * @param  int    $length
 * @return string
 */
function vea_crypt_salt($length)
{
    $alphabet = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $bytes = random_bytes($length);
    $salt = '';
    for ($i = 0; $i < $length; $i++) {
        $salt .= $alphabet[ord($bytes[$i]) % 64];
    }
    return $salt;
}

/**
 * Hash a mailbox password as SHA512-CRYPT ({SHA512-CRYPT}$6$...), which
 * Dovecot understands without further configuration.
 *
 * @param  string $plain
 * @return string
 * @throws RuntimeException when the PHP build cannot produce SHA512-CRYPT
 */
function hash_mail_password($plain)
{
    $salt = vea_crypt_salt(16);
    $hash = crypt($plain, '$6$' . $salt . '$');
    if (!is_string($hash) || strpos($hash, '$6$') !== 0) {
        throw new RuntimeException('this PHP build does not support SHA512-CRYPT');
    }
    return '{SHA512-CRYPT}' . $hash;
}

/* ---------------------------------------------------------------------- */
/* Input validation                                                       */
/* ---------------------------------------------------------------------- */

/**
 * Validate a DNS hostname (1..253 chars, labels 1..63 chars).
 *
 * FILTER_VALIDATE_DOMAIN does not exist on PHP 5.5, so this is done by hand.
 *
 * @param  mixed $name
 * @return bool
 */
function valid_hostname($name)
{
    if (!is_string($name) || $name === '' || strlen($name) > 253) {
        return false;
    }
    $labels = explode('.', $name);
    foreach ($labels as $label) {
        if ($label === '' || strlen($label) > 63) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?$/', $label)) {
            return false;
        }
    }
    return true;
}

/**
 * Validate an email address.
 *
 * @param  mixed $email
 * @return bool
 */
function valid_email($email)
{
    if (!is_string($email) || $email === '' || strlen($email) > 254) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
