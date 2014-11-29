<?php

include ('init.php');

if (!isset($__view) || !$__view) {

  $smarty->display('index.tpl');

} elseif ($__view == "domains") {

  $domains = q($link, "SELECT * FROM virtual_domains");
  $smarty->assign('domains', $domains);
  $smarty->display('domains.tpl');

} elseif ($__view == "domain") {
  $qdomain = $link->quote($__domain);
  $rdomain = q($link, "SELECT * FROM virtual_domains WHERE name = $qdomain");
  $domain = $rdomain[0];
  $users = q($link, "SELECT * FROM virtual_users WHERE domain_id = '" . $domain['id'] . "' ORDER BY email");
  $aliases = q($link, "SELECT * FROM virtual_aliases WHERE domain_id = '" . $domain['id'] . "' ORDER BY source");
  $smarty->assign('domain', $domain);
  $smarty->assign('users', $users);
  $smarty->assign('aliases', $aliases);
  $smarty->display('domain.tpl');

}  

$link = null;
?>
