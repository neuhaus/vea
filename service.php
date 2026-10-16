<?php
/**
 * vea - JSON service endpoint.
 *
 * Every action is a POST carrying a valid CSRF token. All database access uses
 * bound parameters.
 */

require __DIR__ . '/init.php';

require_secure_transport();
require_auth();

if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    header('Allow: POST');
    vea_json_error('method not allowed', 405);
}

csrf_check();

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

try {
    if ($action === 'newdomain') {
        $domain = isset($_POST['domain']) ? trim((string) $_POST['domain']) : '';
        if (!valid_hostname($domain)) {
            vea_json_error('invalid domain name', 400);
        }
        db_exec('INSERT INTO virtual_domains (name) VALUES (?)', array($domain));
        vea_json(array('msg' => 'added domain ' . $domain));

    } elseif ($action === 'deletedomain') {
        $domainId = isset($_POST['domain']) ? (int) $_POST['domain'] : 0;
        if ($domainId <= 0) {
            vea_json_error('invalid domain id', 400);
        }

        $pdo = vea_db();
        $pdo->beginTransaction();
        try {
            db_exec('DELETE FROM virtual_users WHERE domain_id = ?', array($domainId));
            db_exec('DELETE FROM virtual_aliases WHERE domain_id = ?', array($domainId));
            $deleted = db_exec('DELETE FROM virtual_domains WHERE id = ?', array($domainId));
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        if ($deleted < 1) {
            vea_json_error('domain not found', 404);
        }
        vea_json(array('msg' => 'deleted domain'));

    } elseif ($action === 'newuser') {
        $email = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $domainId = isset($_POST['domain']) ? (int) $_POST['domain'] : 0;

        if (!valid_email($email)) {
            vea_json_error('invalid email address', 400);
        }
        if (strlen($password) < 8) {
            vea_json_error('password must be at least 8 characters', 400);
        }
        if ($domainId <= 0) {
            vea_json_error('invalid domain id', 400);
        }

        $hash = hash_mail_password($password);
        db_exec(
            'INSERT INTO virtual_users (email, password, domain_id) VALUES (?, ?, ?)',
            array($email, $hash, $domainId)
        );
        vea_json(array('msg' => 'added new user ' . $email));

    } elseif ($action === 'deleteuser') {
        $userId = isset($_POST['user']) ? (int) $_POST['user'] : 0;
        if ($userId <= 0) {
            vea_json_error('invalid user id', 400);
        }
        $deleted = db_exec('DELETE FROM virtual_users WHERE id = ?', array($userId));
        if ($deleted < 1) {
            vea_json_error('user not found', 404);
        }
        vea_json(array('msg' => 'deleted user'));

    } elseif ($action === 'changeuserpassword') {
        $userId = isset($_POST['user']) ? (int) $_POST['user'] : 0;
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        if ($userId <= 0) {
            vea_json_error('invalid user id', 400);
        }
        if (strlen($password) < 8) {
            vea_json_error('password must be at least 8 characters', 400);
        }

        $hash = hash_mail_password($password);
        $updated = db_exec(
            'UPDATE virtual_users SET password = ? WHERE id = ?',
            array($hash, $userId)
        );
        if ($updated < 1) {
            vea_json_error('user not found', 404);
        }
        vea_json(array('msg' => 'password reset done'));

    } elseif ($action === 'newalias') {
        $source = isset($_POST['alias']) ? trim((string) $_POST['alias']) : '';
        $target = isset($_POST['target']) ? trim((string) $_POST['target']) : '';
        $domainId = isset($_POST['domain']) ? (int) $_POST['domain'] : 0;

        if (!valid_email($source)) {
            vea_json_error('invalid alias source address', 400);
        }
        if (!valid_email($target)) {
            vea_json_error('invalid alias target address', 400);
        }
        if ($domainId <= 0) {
            vea_json_error('invalid domain id', 400);
        }

        db_exec(
            'INSERT INTO virtual_aliases (source, destination, domain_id) VALUES (?, ?, ?)',
            array($source, $target, $domainId)
        );
        vea_json(array('msg' => 'added new alias ' . $source));

    } elseif ($action === 'deletealias') {
        $aliasId = isset($_POST['alias']) ? (int) $_POST['alias'] : 0;
        if ($aliasId <= 0) {
            vea_json_error('invalid alias id', 400);
        }
        $deleted = db_exec('DELETE FROM virtual_aliases WHERE id = ?', array($aliasId));
        if ($deleted < 1) {
            vea_json_error('alias not found', 404);
        }
        vea_json(array('msg' => 'deleted alias'));

    } elseif ($action === 'changealiastarget') {
        $aliasId = isset($_POST['alias']) ? (int) $_POST['alias'] : 0;
        $target = isset($_POST['target']) ? trim((string) $_POST['target']) : '';

        if ($aliasId <= 0) {
            vea_json_error('invalid alias id', 400);
        }
        if (!valid_email($target)) {
            vea_json_error('invalid alias target address', 400);
        }

        $updated = db_exec(
            'UPDATE virtual_aliases SET destination = ? WHERE id = ?',
            array($target, $aliasId)
        );
        if ($updated < 1) {
            vea_json_error('alias not found', 404);
        }
        vea_json(array('msg' => 'UPDATE done'));

    } else {
        vea_json_error('unknown action', 400);
    }
} catch (PDOException $e) {
    error_log('vea: action "' . $action . '" failed: ' . $e->getMessage());
    vea_json_error('database error', 500);
} catch (Exception $e) {
    error_log('vea: action "' . $action . '" failed: ' . $e->getMessage());
    vea_json_error('internal error', 500);
}
