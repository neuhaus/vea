<?php

require('./setup.php');
require('./smarty/libs/Smarty.class.php');
$smarty = new Smarty();

$sitedir = getcwd();
$smarty->template_dir = $sitedir . '/smarty/templates';
$smarty->compile_dir  = $sitedir . '/smarty/templates_c';
$smarty->cache_dir    = $sitedir . '/smarty/cache';
$smarty->config_dir   = $sitedir . '/smarty/configs';

$link = mysql_connect($dbhost, $dbuser, $dbpass) or die('Could not connect: ' . mysql_error());
mysql_select_db($dbname) or die('Could not select database');

import_request_variables('pg', '__');

function q($q) {
  $res = mysql_query($q);
  if (mysql_error()) {
    error_log(mysql_error());
    return (-2);
  }
  $tabs = array();
  if ($res == 1) {
    return(1);
  }
  while ($c = mysql_fetch_array($res, MYSQL_ASSOC)) {
    array_push($tabs, $c);
  }
  mysql_free_result($res);
  return($tabs);
}


?>
