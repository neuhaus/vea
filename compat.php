<?php
/**
 * vea - compatibility shims.
 *
 * Provides the handful of functions that modern PHP has natively but PHP 5.5
 * does not, so the same code runs on PHP 5.5.9 and on PHP 7.x/8.x.
 *
 * This file must stay PHP 5.5 compatible: no short closures, no scalar type
 * hints, no return types, no null-coalescing operator.
 */

if (!function_exists('random_bytes')) {
    /**
     * Cryptographically secure random bytes.
     *
     * @param  int    $length
     * @return string
     * @throws Exception when no secure source is available
     */
    function random_bytes($length)
    {
        $length = (int) $length;
        if ($length < 1) {
            throw new Exception('random_bytes(): length must be greater than 0');
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong === true && strlen($bytes) === $length) {
                return $bytes;
            }
        }

        $fp = @fopen('/dev/urandom', 'rb');
        if ($fp !== false) {
            $bytes = @fread($fp, $length);
            @fclose($fp);
            if ($bytes !== false && strlen($bytes) === $length) {
                return $bytes;
            }
        }

        // Never fall back to a predictable source for secrets.
        throw new Exception('random_bytes(): no cryptographically secure source available');
    }
}

if (!function_exists('hash_equals')) {
    /**
     * Timing-safe string comparison.
     *
     * @param  string $knownString
     * @param  string $userString
     * @return bool
     */
    function hash_equals($knownString, $userString)
    {
        $knownString = (string) $knownString;
        $userString  = (string) $userString;

        $len = strlen($knownString);
        if ($len !== strlen($userString)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $len; $i++) {
            $result |= ord($knownString[$i]) ^ ord($userString[$i]);
        }
        return $result === 0;
    }
}
