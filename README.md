vea
===

Virtual Email Admin
-------------------

vea is an ajax driven virtual email management frontend for a virtual mail
setup (Postfix + Dovecot + PostgreSQL). The database layout follows the
ISPmail guide: <https://workaround.org/ispmail-trixie/database/>.

It manages three tables:

* `virtual_domains` (`id`, `name`)
* `virtual_users` (`id`, `email`, `password`, `domain_id`)
* `virtual_aliases` (`id`, `source`, `destination`, `domain_id`)

Mailbox passwords are stored as `{SHA512-CRYPT}` hashes, which Dovecot reads
without further configuration.

Domain names are entered and stored in their ASCII/Punycode form (for example
`xn--mnchen-3ya.de`), which is what Postfix and Dovecot expect. The interface
also renders the decoded Unicode form (`münchen.de`) next to it. Decoding uses
the PHP `intl` extension's `idn_to_utf8()` when available, otherwise a bundled
Punycode decoder.

This is a fork of the original vea by Felix Wolf; the application has been
hardened and its dependencies brought up to date. See "Security model" and
"Changes" below.

Version
-------

**0.12** - hardened release. Replaces the previous 0.11 line: current Smarty
and jQuery releases, SHA512-CRYPT password hashing only, and enforced HTTP
Basic authentication plus CSRF protection.


Requirements
------------

* PHP **5.5.9+** (also runs on PHP 7.x / 8.x)
* PHP extensions: `pdo_pgsql`, `openssl`, `session`, `json`
* PostgreSQL
* TLS (HTTPS) in front of the app, or access from localhost
* A web server, with HTTP Basic authentication configured for the app


Installation
------------

1. Put the application somewhere in the web root.

2. Serve vea over HTTPS. Plaintext access is accepted only from localhost, so
   requests from any other host are refused unless they arrive over TLS (or
   through a TLS-terminating reverse proxy listed in `VEA_TRUSTED_PROXIES`).

3. Precompile the templates and make the compile directory read-only:

       php tools/compile-templates.php
       chmod -R a-w smarty/templates_c

   vea runs with Smarty's compile check disabled, so the compiled templates
   under `smarty/templates_c/` only need to be readable by the web server and
   never writable.

4. Create the configuration `.env` file from the example and set at least the
   database password:

       cp .env.example .env
       $EDITOR .env

   Keep `.env` out of version control (it is already in `.gitignore`) and
   outside the web root. To use a file outside the web root,
   point vea at it from the web server configuration:

       SetEnv VEA_ENV_FILE /etc/vea/.env

   Values may also be supplied directly as environment variables (Apache
   `SetEnv`, a container environment, etc.); no file is then needed.

5. Configure HTTP Basic authentication. With Apache:

       <Directory /var/www/vea>
           AllowOverride All
           AuthType Basic
           AuthName "vea"
           AuthUserFile /etc/vea/.htpasswd
           Require valid-user
       </Directory>

   and create an account:

       htpasswd -B /etc/vea/.htpasswd admin

   vea does not check passwords itself. It trusts the user name the web server
   reports in `REMOTE_USER` and refuses to serve anything when that is absent,
   so a missing or broken vhost configuration fails closed.

6. Make sure `AllowOverride` permits at least `FileInfo`, `AuthConfig` and
   `Options`, so the shipped `.htaccess` (security headers, blocking of
   `config.php`, `.env` and the `smarty/` tree) takes effect.

   On web servers that ignore `.htaccess` (nginx, Caddy, ...), replicate those
   rules in the server configuration instead: deny access to `smarty/`,
   `tools/`, dotfiles, `config.php`, `init.php`, `compat.php`, `idna.php` and
   `view.php`, and add the security headers. Otherwise the Smarty templates and
   compiled templates under `smarty/` could be served as plain text.


Security model
--------------

* **Authentication** is performed by the web server (HTTP Basic). The
  application only proceeds when the web server sets `REMOTE_USER`; it does
  *not* trust a client-supplied `Authorization` header. An optional
  `VEA_AUTH_USERS` allow-list restricts which login names are accepted.

* **Transport** must be HTTPS, or the request must come from the loopback
  interface (handy for local administration). Plaintext requests from other
  hosts are rejected before any credentials are requested. Behind a
  TLS-terminating proxy, list the proxy in `VEA_TRUSTED_PROXIES` so its
  `X-Forwarded-Proto` header is honoured; otherwise the header is ignored.

* **CSRF**: every state-changing action is a POST that must carry the
  per-session token rendered into the page.

* **SQL injection**: all queries use prepared statements with bound
  parameters.

* **XSS**: Smarty is configured with `escape_html`, so every template variable
  is HTML-escaped.

* **Input validation**: domain names and email addresses are validated before
  they reach the database, and numeric identifiers are cast to integers.

* **Secrets**: the database password lives in `.env` or the environment, never
  in the code, and `config.php` refuses direct web access.

* **Errors**: database errors are logged, never shown to the client; query
  contents (including password hashes) are no longer logged.

* **Headers**: `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-
  Options`, `Referrer-Policy`, `Permissions-Policy` and (over HTTPS) HSTS are
  set in `.htaccess`.


Configuration reference
-----------------------

All settings are read from the environment (or `.env`):

| Variable              | Default        | Purpose                                        |
|-----------------------|----------------|------------------------------------------------|
| `VEA_DB_HOST`         | `127.0.0.1`    | PostgreSQL host                                |
| `VEA_DB_NAME`         | `mailserver`   | Database name                                  |
| `VEA_DB_USER`         | `mailadmin`    | Database user                                  |
| `VEA_DB_PASS`         | *(required)*   | Database password; vea fails closed when unset or empty |
| `VEA_ENV_FILE`        | *(unset)*      | Path to an external `.env` file                |
| `VEA_TRUSTED_PROXIES` | *(empty)*      | Comma-separated reverse-proxy addresses        |
| `VEA_AUTH_USERS`      | *(empty)*      | Comma-separated accepted login names           |
| `VEA_AUTH_REALM`      | `vea`          | HTTP Basic realm name                          |
| `VEA_COMPILE_CHECK`   | *(unset)*      | `1` recompiles changed templates (development) |


Changes from the original
-------------------------

* Replaced the bundled Smarty 2.6.28 with **Smarty 3.1.48** (the newest Smarty
  that still runs on PHP 5.5). The old compiler used `create_function()` and
  `each()`, which no longer exist in PHP 8; the templates only needed minor
  changes.
* Upgraded jQuery to **4.0.0** and rewrote the frontend to use delegated
  `.on()` handlers.
* Added IDN display: Punycode domain names are also shown in their rendered
  Unicode form (`intl` when present, otherwise a bundled Punycode decoder).
* Templates are precompiled ahead of time (`tools/compile-templates.php`) and
  Smarty's compile check is off, so the compile directory can be read-only.
* Replaced `mcrypt` (removed in PHP 7.2) with `random_bytes()` and a small
  polyfill for PHP 5.5.
* Removed `extract($_REQUEST, ...)`, MD5 password hashing and the verbose
  logging of executed statements.
* Moved the database password out of the code into `.env` / the environment.
* Added HTTP Basic auth enforcement, CSRF protection, transport security,
  output escaping, prepared statements, security headers and input validation
  (see "Security model").


Licence
-------

vea is released under the Apache Licence v2 (see `LICENCE`).

Bundled third-party components:

* Smarty 3.1.48 - GNU LGPL 3.0 (see `smarty/LICENCE-Smarty-LGPL.txt`)
* jQuery 4.0.0 - MIT (see <https://jquery.com/license/>)
