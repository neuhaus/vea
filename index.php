<?php
/**
 * vea - page dispatcher.
 *
 * Serves the application shell and the AJAX partials for the domain list and
 * for a single domain.
 */

require __DIR__ . '/init.php';

require_secure_transport();
$user = require_auth();

header('Content-Type: text/html; charset=utf-8');

$smarty = vea_smarty();
$view = isset($_GET['view']) ? (string) $_GET['view'] : '';

if ($view === '') {
    $smarty->assign('csrf_token', csrf_token());
    $smarty->assign('auth_user', $user);
    $smarty->assign('version', vea_version());
    $smarty->display('index.tpl');
    exit;
}

if ($view === 'domains') {
    try {
        $domains = db_query('SELECT id, name FROM virtual_domains ORDER BY name');
    } catch (PDOException $e) {
        error_log('vea: listing domains failed: ' . $e->getMessage());
        $domains = array();
    }
    foreach ($domains as $index => $row) {
        $forms = vea_domain_forms($row['name']);
        $domains[$index]['unicode'] = $forms['unicode'];
        $domains[$index]['label'] = $forms['label'];
    }
    $smarty->assign('domains', $domains);
    $smarty->display('domains.tpl');
    exit;
}

if ($view === 'domain') {
    $name = isset($_GET['domain']) ? (string) $_GET['domain'] : '';

    if (!valid_hostname($name)) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: text/plain; charset=utf-8');
        echo "invalid domain\n";
        exit;
    }

    try {
        $rows = db_query('SELECT id, name FROM virtual_domains WHERE name = ?', array($name));
    } catch (PDOException $e) {
        error_log('vea: loading domain failed: ' . $e->getMessage());
        $rows = array();
    }

    if (count($rows) === 0) {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');
        echo "domain not found\n";
        exit;
    }

    $domain = $rows[0];
    $domainId = (int) $domain['id'];

    $forms = vea_domain_forms($domain['name']);
    $domain['unicode'] = $forms['unicode'];
    $domain['label'] = $forms['label'];

    try {
        $users = db_query(
            'SELECT id, email FROM virtual_users WHERE domain_id = ? ORDER BY email',
            array($domainId)
        );
        $aliases = db_query(
            'SELECT id, source, destination FROM virtual_aliases WHERE domain_id = ? ORDER BY source',
            array($domainId)
        );
    } catch (PDOException $e) {
        error_log('vea: loading domain contents failed: ' . $e->getMessage());
        $users = array();
        $aliases = array();
    }

    $smarty->assign('domain', $domain);
    $smarty->assign('users', $users);
    $smarty->assign('aliases', $aliases);
    $smarty->assign('csrf_token', csrf_token());
    $smarty->display('domain.tpl');
    exit;
}

header('HTTP/1.1 404 Not Found');
header('Content-Type: text/plain; charset=utf-8');
echo "not found\n";
