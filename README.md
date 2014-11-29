vea
===

Virtual Email Admin
-------------------

This is vea, it was released by Felix Wolf around 2010. Its homepage was at http://vea.bar.bz/ but its
site had disappeared from the internet. I'm making the sourcecode available once again. 
**Use at your own risk! Make sure you use it only on an encrypted, access controlled website with trusted users!**

Included in the js directory is a copy of jquery 1.4.2 (minified), which uses the MIT or GPL license.
Also included is the smarty PHP template engine, licensed under the LGPL license.

News
----

vea 0.11 released on 2014-11-29.

This release replaces MySQL with PostgreSQL and adds support for SHA512-CRYPT hashes.
**New: mcrypt is now required.**

The original README is below.
- - -

This is the first version of vea - virtual email admin written by Felix Wolf &lt;felix AT xis DOT to>.
This software is under the Apache License v2. Please read the LICENSE file for details.

vea is an ajax driven virtual email management frontend based on php, smarty templates and jquery.
vea should work together with a configuration described at http://workaround.org/ispmail/etch.

Installation instructions:
 * put archive content somewhere in your webroot
 * make ./smarty/templates_c/ writable by your http-user
 * edit setup.php and change variable values to fit your configuration
 * maybe you want to set permissions for setup.php that only the http-user (ie. www-data) is able to read it
 * make sure you have enabled .htaccess support in your webserver configuration
 * create a http-authentication-user (ie. "htpasswd .htpasswd admin")

Please note:
------------
**This "maybe a bit crappy piece of software" was not devolped with any security thoughts in mind.
Make sure you use it with SSL, only accessible in your management LAN or management VPN in
production environments.**
