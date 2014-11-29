<?php

include ('init.php');

if ($__action == "newdomain") {
  $domain = $link->quote($__domain);
  if (!$domain) {
    error("no domain name given");
  }
  $r = x($link, "INSERT INTO virtual_domains (name) VALUES ($domain)");
  if ($r == "-2") {
    error("database error");
  }
  msg("added domain " . $domain);

} elseif ($__action == "deletedomain") {
  $domain = $link->quote($__domain);
  if (!$domain) {
    error("no domain id given");
  }
  $r = x($link, "DELETE FROM virtual_domains WHERE id = $domain" );
  if ($r == "-2") {
    error("database error");
  }
  $r = x($link, "DELETE FROM virtual_users WHERE domain_id = $domain" );
  if ($r == "-2") {
    error("database error");
  }
  $r = x($link, "DELETE FROM virtual_aliases WHERE domain_id = $domain" );
  if ($r == "-2") {
    error("database error");
  }
  msg("deleted domain " . $domain);

} elseif ($__action == "newuser") {
  $username = $link->quote($__username);
  if (CRYPT_SHA512 == 1) {
    // 16 byte salt, base64 from 13 random bytes
    $salt = substr(base64_encode(mcrypt_create_iv(13, MCRYPT_DEV_URANDOM)), 0, 16);
    $password = $link->quote('{SHA512-CRYPT}' . crypt($__password, "\$6\$$salt\$"));
  } else {
    $password = $link->quote(md5($__password));
  }
  $domain   = $link->quote($__domain, PDO::PARAM_INT);

  if (!$domain or !$username or !$__password) {
    error("not all necessary data given");
  }

  $r = x($link, "INSERT INTO virtual_users (email, password, domain_id) VALUES ($username,$password,$domain)");
  if ($r == "-2") {
    error("database error");
  }
  msg("added new user " . $username);

} elseif ($__action == "deleteuser") {
  $user = $link->quote($__user);

  if (!$user) {
    error("no user id given");
  }

  $r = x($link, "DELETE FROM virtual_users WHERE id = $user");
  if ($r == "-2") {
    error("database error");
  }
  msg("Deleted user with id " . $user);

} elseif ($__action == "changeuserpassword") {
  $user     = $link->quote($__user);
  if (CRYPT_SHA512 == 1) {
    // 16 byte salt, base64 from 13 random bytes
    $salt = substr(base64_encode(mcrypt_create_iv(13, MCRYPT_DEV_URANDOM)), 0, 16);
    $password = $link->quote('{SHA512-CRYPT}' . crypt($__password, "\$6\$$salt\$"));
  } else {
    $password = $link->quote(md5($__password));
  }

  if (!$user or !$__password) {
    error("no userid or password given");
  }

  $r = x($link, "UPDATE virtual_users SET password = $password WHERE id = $user");
  if ($r == "-2") {
    error("database error");
  }
  msg("password reset done");

} elseif ($__action == "newalias") {
  $alias = $link->quote($__alias);
  $target = $link->quote($__target);
  $domain = $link->quote($__domain);

  if (!$domain or !$target) {
    error("not all necessary data given");
  }

  $r = x($link, "INSERT INTO virtual_aliases (source, destination, domain_id) VALUES ($alias,$target,$domain)");
  if ($r == "-2") {
    error("database error");
  }
  msg("added new alias " . $alias);

} elseif ($__action == "deletealias") {
  $alias = $link->quote($__alias);

  if (!$alias) {
    error("no alias id given");
  }
  $r = x($link, "DELETE FROM virtual_aliases WHERE id = $alias");
  if ($r == "-2") {
    error("database error");
  }
  msg("deleted alias with id " . $alias);

} elseif ($__action == "changealiastarget") {
  $alias = $link->quote($__alias);
  $target = $link->quote($__target);

  if (!$alias or !$target) {
    error("no password given");
  }

  $r = x($link, "UPDATE virtual_aliases SET destination = $target WHERE id = $alias");
  if ($r == "-2") {
    error("database error");
  }
  msg("UPDATE done");
}

$link = null;

function msg($msg) {
  print json_encode(array("msg" => $msg));
}

function error($msg) {
  print json_encode(array("error" => $msg));
  exit;
}

?>
