<?php

require('./setup.php');
require('./smarty/libs/Smarty.class.php');
$smarty = new Smarty();

$sitedir = getcwd();
$smarty->template_dir = $sitedir . '/smarty/templates';
$smarty->compile_dir  = $sitedir . '/smarty/templates_c';
$smarty->cache_dir    = $sitedir . '/smarty/cache';
$smarty->config_dir   = $sitedir . '/smarty/configs';

$link = new PDO("pgsql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING); 
x($link,"SET AUTOCOMMIT = ON");

extract($_REQUEST, EXTR_PREFIX_ALL|EXTR_REFS, '_');

function q($link,$q) {
  try {
	  $res = $link->query($q);
  } catch (PDOException $e) {
	error_log($e->getMessage());
	return (-2);
  }
  $tabs = array();
  while ($c = $res->fetch(PDO::FETCH_ASSOC)) {
    array_push($tabs, $c);
  }
  $res->closeCursor();
  return($tabs);
}

function x($link,$q) {
  try {
    $res = $link->exec($q);
    error_log("executed statement OK: " . $q);
  } catch (PDOException $e) {
    error_log($e->getMessage());
    return (-2);
  }
  return(1);
}

?>
