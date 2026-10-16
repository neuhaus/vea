<?php
/**
 * vea - IDN (internationalized domain name) display helpers.
 *
 * Domains are stored and entered in their ASCII/Punycode form ("xn--..."),
 * which is what Postfix and Dovecot expect. For display we render the decoded
 * Unicode form as well.
 *
 * If the intl extension is available its idn_to_utf8() is used; otherwise a
 * small, dependency-free Punycode (RFC 3492) decoder is used.
 *
 * This file must stay PHP 5.5 compatible.
 */

/**
 * Decode one Punycode label (without the "xn--" prefix) to UTF-8.
 *
 * @param  string       $input
 * @return string|bool  UTF-8 string, or false on malformed input
 */
function vea_punycode_decode($input)
{
    $base = 36;
    $tmin = 1;
    $tmax = 26;
    $damp = 700;
    $initialBias = 72;
    $initialN = 128;

    $n = $initialN;
    $i = 0;
    $bias = $initialBias;
    $output = array();

    $len = strlen($input);
    $delim = strrpos($input, '-');
    if ($delim !== false) {
        for ($j = 0; $j < $delim; $j++) {
            $output[] = ord($input[$j]);
        }
        $index = $delim + 1;
    } else {
        $index = 0;
    }

    while ($index < $len) {
        $oldi = $i;
        $w = 1;

        for ($k = $base; ; $k += $base) {
            if ($index >= $len) {
                return false;
            }
            $digit = vea_punycode_value($input[$index++]);
            if ($digit === false) {
                return false;
            }
            $i += $digit * $w;

            $t = ($k <= $bias) ? $tmin : (($k >= $bias + $tmax) ? $tmax : ($k - $bias));
            if ($digit < $t) {
                break;
            }
            $w *= ($base - $t);
            if ($w > 0x7FFFFFFF) {
                return false;
            }
        }

        $count = count($output) + 1;
        $bias = vea_punycode_adapt($i - $oldi, $count, $oldi === 0);
        $n += (int) ($i / $count);
        if ($n > 0x10FFFF) {
            return false;
        }
        $i = $i % $count;
        array_splice($output, $i, 0, array($n));
        $i++;
    }

    $result = '';
    foreach ($output as $cp) {
        $result .= vea_codepoint_to_utf8($cp);
    }
    return $result;
}

/**
 * Punycode bias adaptation (RFC 3492 section 6.1).
 *
 * @param  int $delta
 * @param  int $numpoints
 * @param  bool $firsttime
 * @return int
 */
function vea_punycode_adapt($delta, $numpoints, $firsttime)
{
    $delta = $firsttime ? (int) ($delta / 700) : (int) ($delta / 2);
    $delta += (int) ($delta / $numpoints);
    $k = 0;
    while ($delta > 455) {
        $delta = (int) ($delta / 35);
        $k += 36;
    }
    return $k + (int) ((36 * $delta) / ($delta + 38));
}

/**
 * Value of a Punycode digit character.
 *
 * @param  string       $c
 * @return int|bool
 */
function vea_punycode_value($c)
{
    $o = ord($c);
    if ($o >= 0x61 && $o <= 0x7A) {
        return $o - 0x61;          // a-z
    }
    if ($o >= 0x41 && $o <= 0x5A) {
        return $o - 0x41;          // A-Z
    }
    if ($o >= 0x30 && $o <= 0x39) {
        return $o - 0x30 + 26;     // 0-9
    }
    return false;
}

/**
 * Encode one Unicode code point as UTF-8.
 *
 * @param  int    $cp
 * @return string
 */
function vea_codepoint_to_utf8($cp)
{
    if ($cp < 0x80) {
        return chr($cp);
    }
    if ($cp < 0x800) {
        return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
    }
    if ($cp < 0x10000) {
        return chr(0xE0 | ($cp >> 12))
            . chr(0x80 | (($cp >> 6) & 0x3F))
            . chr(0x80 | ($cp & 0x3F));
    }
    return chr(0xF0 | ($cp >> 18))
        . chr(0x80 | (($cp >> 12) & 0x3F))
        . chr(0x80 | (($cp >> 6) & 0x3F))
        . chr(0x80 | ($cp & 0x3F));
}

/**
 * Decode an ASCII/Punycode domain name to its Unicode form for display.
 *
 * Labels that are not Punycode are left untouched. If decoding fails the
 * original name is returned unchanged.
 *
 * @param  string $domain
 * @return string
 */
function vea_domain_unicode($domain)
{
    if (!is_string($domain) || $domain === '') {
        return $domain;
    }

    if (function_exists('idn_to_utf8')) {
        $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
        $result = @idn_to_utf8($domain, 0, $variant);
        if ($result !== false && $result !== '') {
            return $result;
        }
        return $domain;
    }

    $labels = explode('.', $domain);
    foreach ($labels as $index => $label) {
        if (strncasecmp($label, 'xn--', 4) === 0) {
            $decoded = vea_punycode_decode(substr($label, 4));
            if ($decoded !== false && $decoded !== '') {
                $labels[$index] = $decoded;
            }
        }
    }
    return implode('.', $labels);
}

/**
 * Compute the display forms of a domain name.
 *
 * @param  string $name ASCII/Punycode domain name as stored in the database
 * @return array  with keys "ascii", "unicode" and "label"
 */
function vea_domain_forms($name)
{
    $ascii = is_string($name) ? $name : '';
    $unicode = vea_domain_unicode($ascii);

    if ($unicode !== '' && $unicode !== $ascii) {
        $label = $unicode . ' (' . $ascii . ')';
    } else {
        $label = $ascii;
    }

    return array('ascii' => $ascii, 'unicode' => $unicode, 'label' => $label);
}
