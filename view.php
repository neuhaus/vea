<?php
/**
 * vea - Smarty factory.
 *
 * Kept separate from init.php so the compile tool (tools/compile-templates.php)
 * can build the exact same Smarty instance without needing the database
 * configuration.
 *
 * This file must stay PHP 5.5 compatible.
 */

// Refuse to run when requested directly rather than via init.php / the tool.
if (!defined('VEA_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    exit("Forbidden\n");
}

if (!function_exists('vea_smarty')) {
    /**
     * Shared Smarty instance.
     *
     * Output is auto-escaped (escape_html) as an XSS defence and caching is off
     * because every response is user specific.
     *
     * Templates are expected to be precompiled (see tools/compile-templates.php),
     * so compile_check is off by default and the compile directory may be made
     * read-only. Set VEA_COMPILE_CHECK=1 while editing templates on a writable
     * compile directory.
     *
     * @return Smarty
     */
    function vea_smarty()
    {
        static $smarty = null;

        if ($smarty === null) {
            $smarty = new Smarty();
            $smarty->setTemplateDir(VEA_ROOT . '/smarty/templates');
            $smarty->setCompileDir(VEA_ROOT . '/smarty/templates_c');
            $smarty->setCacheDir(VEA_ROOT . '/smarty/cache');
            $smarty->setConfigDir(VEA_ROOT . '/smarty/configs');
            $smarty->setEscapeHtml(true);
            $smarty->caching = Smarty::CACHING_OFF;
            $smarty->compile_check = (getenv('VEA_COMPILE_CHECK') === '1');
            $smarty->php_handling = Smarty::PHP_REMOVE;
        }
        return $smarty;
    }
}
