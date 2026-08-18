<?php

use App\Casts\EncryptedCredential;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts the media-server credentials held in the settings row.
 *
 * Ciphertext is far longer than the plaintext it replaces, so the columns move
 * from varchar(255) to text first. Values are read and written through the
 * query builder rather than the model so the cast does not encrypt twice, and
 * anything already encrypted is skipped - this is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            foreach (Setting::ENCRYPTED_CREDENTIALS as $column) {
                $table->text($column)->nullable()->change();
            }
        });

        foreach (DB::table('settings')->get() as $row) {
            $updates = [];

            foreach (Setting::ENCRYPTED_CREDENTIALS as $column) {
                $value = $row->{$column} ?? null;

                if ($value === null || $value === '' || EncryptedCredential::looksEncrypted($value)) {
                    continue;
                }

                $updates[$column] = Crypt::encryptString((string) $value);
            }

            if ($updates !== []) {
                DB::table('settings')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('settings')->get() as $row) {
            $updates = [];

            foreach (Setting::ENCRYPTED_CREDENTIALS as $column) {
                $value = $row->{$column} ?? null;

                if ($value === null || $value === '' || ! EncryptedCredential::looksEncrypted($value)) {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (DecryptException) {
                    // Nothing recoverable here; leave the column untouched.
                }
            }

            if ($updates !== []) {
                DB::table('settings')->where('id', $row->id)->update($updates);
            }
        }
    }
};
