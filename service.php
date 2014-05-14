<?php

include ('init.php');

if ($__action == "newdomain") {
  $domain = mysql_real_escape_string($__domain);
  if (!$domain) {
    error("no domain name given");
  }
  $r = q("insert into virtual_domains (name) values ('" . $domain . "')");
  if ($r == "-2") {
    error("database error");
  }
  msg("added domain " . $domain);

} elseif ($__action == "deletedomain") {
  $domain = mysql_real_escape_string($__domain);
  if (!$domain) {
    error("no domain id given");
  }
  $r = q("delete from virtual_domains where id = '" . $domain . "'");
  if ($r == "-2") {
    error("database error");
  }
  $r = q("delete from virtual_users where domain_id = '" . $domain . "'");
  if ($r == "-2") {
    error("database error");
  }
  $r = q("delete from virtual_aliases where domain_id = '" . $domain . "'");
  if ($r == "-2") {
    error("database error");
  }
  msg("deleted domain " . $domain);

} elseif ($__action == "newuser") {
  $username = mysql_real_escape_string($__username);
  $password = mysql_real_escape_string($__password);
  $domain = mysql_real_escape_string($__domain);

  if (!$domain or !$username or !$password) {
    error("not all necessary data given");
  }

  $r = q("insert into virtual_users (email, password, domain_id) values ('" . $username . "','" . md5($password) . "','" . $domain . "')");
  if ($r == "-2") {
    error("database error");
  }
  msg("added new user " . $username);

} elseif ($__action == "deleteuser") {
  $user = mysql_real_escape_string($__user);

  if (!$user) {
    error("no user id given");
  }

  $r = q("delete from virtual_users where id = '" . $user . "'");
  if ($r == "-2") {
    error("database error");
  }
  msg("deleted user with id " . $user);

} elseif ($__action == "changeuserpassword") {
  $user = mysql_real_escape_string($__user);
  $password = mysql_real_escape_string($__password);

  if (!$user or !$password) {
    error("no userid or password given");
  }

  $r = q("update virtual_users set password = '" . md5($password) . "' where id = '" . $user . "'");
  if ($r == "-2") {
    error("database error");
  }
  msg("password reset done");

} elseif ($__action == "newalias") {
  $alias = mysql_real_escape_string($__alias);
  $target = mysql_real_escape_string($__target);
  $domain = mysql_real_escape_string($__domain);

  if (!$domain or !$target) {
    error("not all necessary data given");
  }

  $r = q("insert into virtual_aliases (source, destination, domain_id) values ('" . $alias . "','" . $target . "','" . $domain . "')");
  if ($r == "-2") {
    error("database error");
  }
  msg("added new alias " . $alias);

} elseif ($__action == "deletealias") {
  $alias = mysql_real_escape_string($__alias);

  if (!$alias) {
    error("no alias id given");
  }
  $r = q("delete from virtual_aliases where id = '" . $alias . "'");
  if ($r == "-2") {
    error("database error");
  }
  msg("deleted alias with id " . $alias);

} elseif ($__action == "changealiastarget") {
  $alias = mysql_real_escape_string($__alias);
  $target = mysql_real_escape_string($__target);

  if (!$alias or !$target) {
    error("no password given");
  }

  $r = q("update virtual_aliases set destination = '" . $target . "' where id = '" . $alias . "'");
  if ($r == "-2") {
    error("database error");
  }
  msg("update done");
}


mysql_close($link);

function msg($msg) {
  print json_encode(array("msg" => $msg));
}

function error($msg) {
  print json_encode(array("error" => $msg));
  exit;
}

?>
