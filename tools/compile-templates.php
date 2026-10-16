<?php
/**
 * vea - precompile Smarty templates.
 *
 * Compiles every template in smarty/templates into smarty/templates_c so the
 * compile directory can be made read-only in production. The application runs
 * with compile_check disabled (see view.php), so it only ever reads these
 * files.
 *
 * Run this after deploying template changes:
 *
 *     php tools/compile-templates.php
 *
 * then lock the directory down, for example:
 *
 *     chown -R root:root smarty/templates_c
 *     chmod -R a-w smarty/templates_c
 *
 * This file must stay PHP 5.5 compatible.
 */

define('VEA_ROOT', dirname(__DIR__));

require VEA_ROOT . '/smarty/libs/Smarty.class.php';
require VEA_ROOT . '/view.php';

$smarty = vea_smarty();
$compileDir = $smarty->getCompileDir();

if (!is_dir($compileDir)) {
    fwrite(STDERR, 'compile directory does not exist: ' . $compileDir . "\n");
    exit(1);
}

if (!is_writable($compileDir)) {
    fwrite(STDERR, 'compile directory is not writable: ' . $compileDir . "\n");
    fwrite(STDERR, "Make it writable for this step, then make it read-only again.\n");
    exit(1);
}

// compileAllTemplates() prints progress to stdout; capture and discard it on
// success, surface it on failure.
ob_start();
$count = $smarty->compileAllTemplates('.tpl', true);
$output = ob_get_clean();

if (strpos($output, 'Error:') !== false) {
    fwrite(STDERR, "template compilation failed:\n");
    fwrite(STDERR, trim($output) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf("compiled %d template(s) into %s\n", $count, $compileDir));
exit(0);
