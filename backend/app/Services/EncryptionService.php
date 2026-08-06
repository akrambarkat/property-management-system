<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Central service for encrypting sensitive configuration values (e.g. SMS
 * gateway credentials) at rest. Wraps Laravel's Crypt so the encryption
 * strategy can be audited or swapped without touching callers.
 */
class EncryptionService
{
    /**
     * Encrypt a plaintext secret. Empty values are returned as-is so we never
     * store ciphertext for blank credentials.
     */
    public function encrypt(string $value): string
    {
        return $value === '' ? '' : Crypt::encryptString($value);
    }

    /**
     * Decrypt a stored secret. Values that are not valid ciphertext (legacy
     * plaintext rows, empty strings) are returned unchanged so existing data
     * keeps working after enabling encryption.
     */
    public function decrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return $value;
        }
    }
}
