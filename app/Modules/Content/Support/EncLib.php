<?php

namespace App\Modules\Content\Support;

/**
 * CI Enc_lib — AES-256-CBC tokens used in site/share URLs.
 */
class EncLib
{
    public const PUB_KEY = 'ss@pubkey';

    public const PVT_KEY = 'ss@pvtkey';

    public static function encrypt(string $string): string
    {
        $key = hash('sha256', self::PVT_KEY);
        $iv = substr(hash('sha256', self::PUB_KEY), 0, 16);
        $encrypted = openssl_encrypt($string, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode((string) $encrypted);
    }

    public static function dycrypt(string $string): string|false
    {
        $key = hash('sha256', self::PVT_KEY);
        $iv = substr(hash('sha256', self::PUB_KEY), 0, 16);
        $decoded = base64_decode($string, true);
        if ($decoded === false) {
            return false;
        }

        return openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
    }
}
