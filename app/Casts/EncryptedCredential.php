<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Encrypts a credential at rest so a copied database file is not also a set of
 * working media-server tokens.
 *
 * This is deliberately more forgiving than Laravel's built-in "encrypted" cast
 * in two ways:
 *
 *  - Values that are not already ciphertext are passed straight through. That
 *    makes the encrypting migration idempotent, and means a value written
 *    directly into the database by hand still works.
 *  - A value that cannot be decrypted degrades to null instead of throwing.
 *    APP_KEY going missing should mean "re-enter your Plex token", not a 500
 *    on the unauthenticated settings endpoint that the kiosk display polls.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedCredential implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! self::looksEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            Log::warning(
                "Could not decrypt {$model->getTable()}.{$key}. This usually means APP_KEY "
                .'changed since the value was saved; re-enter the credential to fix it.'
            );

            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (self::looksEncrypted($value)) {
            return $value;
        }

        return Crypt::encryptString((string) $value);
    }

    /**
     * Laravel serialises ciphertext as base64 of a JSON payload carrying iv,
     * value and mac. Checking for that shape lets plaintext through untouched.
     */
    public static function looksEncrypted(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}
