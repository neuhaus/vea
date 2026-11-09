<?php
/**
 * vea self-checks - no test framework required.
 *
 *     php tests/selfcheck.php
 *
 * Exits non-zero on failure. It does not touch the database (init.php only
 * connects lazily), so no credentials are needed.
 *
 * This file must stay PHP 5.5 compatible.
 */

$app = getenv('VEA_APP');
if ($app === false || $app === '') {
    $app = dirname(__DIR__);
}

putenv('VEA_DB_PASS=test'); // config.php fails closed without a password
require $app . '/init.php';

$checks = 0;
$failures = 0;

function t($label, $got, $want)
{
    global $checks, $failures;
    $checks++;
    if ($got !== $want) {
        $failures++;
        fwrite(STDERR, 'FAIL ' . $label . "\n  got:  " . var_export($got, true)
            . "\n  want: " . var_export($want, true) . "\n");
    }
}

/* --- IDN / Punycode rendering ---------------------------------------- */

t('punycode mnchen-3ya', vea_punycode_decode('mnchen-3ya'), 'münchen');
t('punycode bcher-kva', vea_punycode_decode('bcher-kva'), 'bücher');
t('punycode chinese', vea_punycode_decode('ihqwcrb4cv8a8dqg056pqjye'), '他们为什么不说中文');
t('punycode czech', vea_punycode_decode('Proprostnemluvesky-uyb24dma41a'), 'Pročprostěnemluvíčesky');
t('punycode japanese', vea_punycode_decode('3B-ww4c5e180e575a65lsy2b'), '3年B組金八先生');
t('punycode russian', vea_punycode_decode('b1abfaaepdrnnbgefbadotcwatmq2g4l'), 'почемужеонинеговорятпорусски');
t('decode garbage is false', vea_punycode_decode('!!!'), false);
t('domain unicode', vea_domain_unicode('xn--mnchen-3ya.de'), 'münchen.de');
t('ascii domain untouched', vea_domain_unicode('example.com'), 'example.com');
t('mixed label', vea_domain_unicode('mail.xn--bcher-kva.de'), 'mail.bücher.de');
t('garbage falls back', vea_domain_unicode('xn--@@@.com'), 'xn--@@@.com');

$forms = vea_domain_forms('xn--mnchen-3ya.de');
t('forms ascii', $forms['ascii'], 'xn--mnchen-3ya.de');
t('forms unicode', $forms['unicode'], 'münchen.de');
t('forms label', $forms['label'], 'münchen.de (xn--mnchen-3ya.de)');
t('plain label', vea_domain_forms('example.com')['label'], 'example.com');

/* --- address-in-domain ---------------------------------------------- */

t('bare local part', vea_address_in_domain('jane', 'example.com'), 'jane@example.com');
t('full address same domain', vea_address_in_domain('jane@example.com', 'example.com'), 'jane@example.com');
t('case-insensitive domain', vea_address_in_domain('jane@EXAMPLE.com', 'example.com'), 'jane@example.com');
t('dotted local part', vea_address_in_domain('first.last', 'example.com'), 'first.last@example.com');
t('trims whitespace', vea_address_in_domain('  jane  ', 'example.com'), 'jane@example.com');
t('single-label domain', vea_address_in_domain('jane', 'singlelabel'), 'jane@singlelabel');
t('reject other domain', vea_address_in_domain('jane@other.com', 'example.com'), false);
t('reject empty', vea_address_in_domain('', 'example.com'), false);
t('reject catch-all', vea_address_in_domain('@example.com', 'example.com'), false);
t('reject space', vea_address_in_domain('a b', 'example.com'), false);
t('reject leading dot', vea_address_in_domain('.bad', 'example.com'), false);
t('reject trailing dot', vea_address_in_domain('bad.', 'example.com'), false);
t('reject double dot', vea_address_in_domain('a..b', 'example.com'), false);
t('reject double at', vea_address_in_domain('a@b@example.com', 'example.com'), false);
t('reject empty domain', vea_address_in_domain('jane', ''), false);

/* --- validation and hashing ----------------------------------------- */

t('hostname valid', valid_hostname('sub.mail.example.com'), true);
t('hostname rejects leading hyphen', valid_hostname('-bad.com'), false);
t('hostname rejects empty label', valid_hostname('a..b'), false);
t('hostname rejects 64-char label', valid_hostname(str_repeat('a', 64) . '.com'), false);
t('email valid', valid_email('user@example.com'), true);
t('email rejects bare host', valid_email('user@example'), false);

$hash = hash_mail_password('secret123');
$raw = substr($hash, strlen('{SHA512-CRYPT}'));
t('hash prefix', strpos($hash, '{SHA512-CRYPT}$6$'), 0);
t('hash verifies', crypt('secret123', $raw) === $raw, true);

echo $checks . ' checks, ' . $failures . " failures\n";
exit($failures === 0 ? 0 : 1);
