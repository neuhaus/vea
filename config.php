<?php
/**
 * vea - configuration loader and defaults.
 *
 * This file contains no secrets and is safe to keep in version control. The
 * database password and any other per-installation values are read from the
 * environment. For local setups they may also be placed in a ".env" file.
 *
 * Loading order (first value found wins):
 *   1. real environment variables
 *      (e.g. Apache "SetEnv VEA_DB_PASS ...", or a container's env)
 *   2. the file named by the VEA_ENV_FILE environment variable
 *   3. <app directory>/.env
 *
 * For best security keep the .env file OUTSIDE the web root and point
 * VEA_ENV_FILE at it, or set the variables in the web server / container and
 * use no file at all. See README.md and .env.example.
 *
 * This file must stay PHP 5.5 compatible.
 */

// Refuse to run when requested directly rather than via init.php.
if (!defined('VEA_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

if (!function_exists('vea_load_env')) {
    /**
     * Parse a minimal KEY=VALUE ".env" file into the process environment.
     *
     * Real environment variables are never overwritten. Blank lines and lines
     * starting with "#" are ignored; surrounding single or double quotes are
     * stripped.
     *
     * @param  string $file
     * @return bool   true when a readable file was parsed
     */
    function vea_load_env($file)
    {
        if (!is_string($file) || $file === '' || !is_readable($file)) {
            return false;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($key === '' || getenv($key) !== false) {
                continue;
            }
            $length = strlen($value);
            if ($length >= 2) {
                $first = $value[0];
                $last = $value[$length - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, $length - 2);
                }
            }
            putenv($key . '=' . $value);
        }
        return true;
    }

    /**
     * Read a trimmed environment value with a default.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    function vea_env($name, $default)
    {
        $value = getenv($name);
        return ($value === false || $value === '') ? $default : $value;
    }

    /**
     * Read a comma-separated environment value as an array of strings.
     *
     * @param  string $name
     * @return array
     */
    function vea_env_list($name)
    {
        $value = getenv($name);
        if ($value === false || trim($value) === '') {
            return array();
        }
        $items = array();
        foreach (explode(',', $value) as $item) {
            $item = trim($item);
            if ($item !== '') {
                $items[] = $item;
            }
        }
        return $items;
    }
}

// Load the ".env" file, if any. Real environment variables take precedence.
$explicitEnv = getenv('VEA_ENV_FILE');
if ($explicitEnv !== false && $explicitEnv !== '') {
    vea_load_env($explicitEnv);
}
vea_load_env(VEA_ROOT . '/.env');

/* --- Database (PostgreSQL) -------------------------------------------- */

define('DB_HOST', vea_env('VEA_DB_HOST', '127.0.0.1'));
define('DB_NAME', vea_env('VEA_DB_NAME', 'mailserver'));
define('DB_USER', vea_env('VEA_DB_USER', 'mailadmin'));
define('DB_PASS', vea_env('VEA_DB_PASS', ''));

if (DB_PASS === '') {
    // Fail closed rather than run against the database with an empty password.
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain; charset=utf-8');
    exit("vea: VEA_DB_PASS is not configured (see .env.example)\n");
}

/* --- Transport security ------------------------------------------------
 * vea refuses to serve anything unless the request arrived over HTTPS or from
 * the loopback interface. TLS terminated by a reverse proxy is recognised when
 * the proxy's address is listed in VEA_TRUSTED_PROXIES.
 */
$GLOBALS['TRUSTED_PROXIES'] = vea_env_list('VEA_TRUSTED_PROXIES');

/* --- Authentication ----------------------------------------------------
 * vea does not authenticate users itself. It relies on HTTP Basic auth
 * configured in the web server and refuses to serve anything unless the
 * server authenticated the request (i.e. it set REMOTE_USER).
 *
 * See README.md for the required Apache configuration.
 */
define('AUTH_REALM', vea_env('VEA_AUTH_REALM', 'vea'));

/*
 * Optional allow-list of accepted usernames (VEA_AUTH_USERS, comma-separated).
 * Empty means: accept any username the web server authenticated.
 */
$GLOBALS['AUTH_USERS'] = vea_env_list('VEA_AUTH_USERS');
