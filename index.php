<?php

include ('init.php');

if (!$__view) {

  $smarty->display('index.tpl');

} elseif ($__view == "domains") {

  $domains = q("select * from virtual_domains");
  $smarty->assign('domains', $domains);
  $smarty->display('domains.tpl');

} elseif ($__view == "domain") {

  $rdomain = q("select * from virtual_domains where name = '" . $__domain . "'");
  $domain = $rdomain[0];
  $users = q("select * from virtual_users where domain_id = '" . $domain['id'] . "' order by email");
  $aliases = q("select * from virtual_aliases where domain_id = '" . $domain['id'] . "' order by source");
  $smarty->assign('domain', $domain);
  $smarty->assign('users', $users);
  $smarty->assign('aliases', $aliases);
  $smarty->display('domain.tpl');

}  

mysql_close($link);
?>
