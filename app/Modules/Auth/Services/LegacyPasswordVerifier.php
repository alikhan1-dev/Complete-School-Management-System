<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;

/**
 * Bridge for legacy password formats used by Smart School.
 * Detects stored format, verifies, then upgrades to bcrypt on success.
 */
class LegacyPasswordVerifier
{
    public function check(string $plain, string $stored): bool
    {
        $stored = (string) $stored;

        if ($stored === '') {
            return false;
        }

        // bcrypt / argon
        if (preg_match('/^\$(2[aby]|argon2)/', $stored) === 1) {
            return Hash::check($plain, $stored);
        }

        // MD5 (32 hex)
        if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
            return hash_equals(strtolower($stored), md5($plain));
        }

        // SHA1 (40 hex)
        if (preg_match('/^[a-f0-9]{40}$/i', $stored) === 1) {
            return hash_equals(strtolower($stored), sha1($plain));
        }

        // Plaintext parity with CI User_model::checkLogin
        return hash_equals($stored, $plain);
    }

    public function needsRehash(string $stored): bool
    {
        if (preg_match('/^\$(2[aby]|argon2)/', $stored) !== 1) {
            return true;
        }

        return Hash::needsRehash($stored);
    }

    public function hash(string $plain): string
    {
        return Hash::make($plain);
    }
}
