<?php

namespace Tests\Feature;

use App\Casts\EncryptedCredential;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Media-server credentials are encrypted at rest, so a copied database file is
 * not also a working set of tokens.
 */
class CredentialEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private function raw(string $column): ?string
    {
        return DB::table('settings')->value($column);
    }

    #[DataProvider('credentialColumns')]
    public function test_a_credential_is_stored_as_ciphertext(string $column): void
    {
        $plain = 'secret-value-for-'.$column;

        Setting::firstOrFail()->forceFill([$column => $plain])->save();

        $stored = $this->raw($column);

        $this->assertNotSame($plain, $stored, "{$column} was stored in plain text");
        $this->assertStringNotContainsString($plain, (string) $stored);
        $this->assertTrue(EncryptedCredential::looksEncrypted($stored));
        $this->assertSame($plain, Setting::firstOrFail()->{$column});
    }

    public static function credentialColumns(): array
    {
        return array_map(fn ($c) => [$c], Setting::ENCRYPTED_CREDENTIALS);
    }

    public function test_every_credential_column_is_cast(): void
    {
        $casts = (new Setting)->getCasts();

        foreach (Setting::ENCRYPTED_CREDENTIALS as $column) {
            $this->assertSame(EncryptedCredential::class, $casts[$column] ?? null);
        }
    }

    public function test_saving_twice_does_not_encrypt_twice(): void
    {
        $settings = Setting::firstOrFail();
        $settings->plex_token = 'stable-token';
        $settings->save();

        $first = $this->raw('plex_token');

        // Re-saving without changing the value must not wrap it again.
        $settings = Setting::firstOrFail();
        $settings->coming_soon_text = 'Something else';
        $settings->save();

        $this->assertSame('stable-token', Setting::firstOrFail()->plex_token);
        $this->assertTrue(EncryptedCredential::looksEncrypted($this->raw('plex_token')));
        $this->assertSame(
            'stable-token',
            Crypt::decryptString((string) $first),
            'The first write should decrypt to the original value.'
        );
    }

    public function test_null_and_empty_values_are_left_alone(): void
    {
        Setting::firstOrFail()->forceFill(['plex_token' => null, 'jellyfin_token' => ''])->save();

        $this->assertNull($this->raw('plex_token'));
        $this->assertSame('', $this->raw('jellyfin_token'));
        $this->assertNull(Setting::firstOrFail()->plex_token);
    }

    public function test_plaintext_already_in_the_column_is_still_readable(): void
    {
        // Simulates a value written before the encrypting migration ran, or by
        // hand. It should be returned as-is rather than failing to decrypt.
        DB::table('settings')->update(['plex_token' => 'legacy-plaintext']);

        $this->assertSame('legacy-plaintext', Setting::firstOrFail()->plex_token);
    }

    public function test_an_undecryptable_value_degrades_to_null_rather_than_throwing(): void
    {
        // What a rotated or lost APP_KEY looks like. The display polls the
        // settings endpoint and must not be taken down by this.
        DB::table('settings')->update([
            'plex_token' => base64_encode(json_encode([
                'iv' => base64_encode(random_bytes(16)),
                'value' => 'not-actually-decryptable',
                'mac' => str_repeat('0', 64),
            ])),
        ]);

        $this->assertNull(Setting::firstOrFail()->plex_token);

        $this->getJson('/api/settings')->assertOk();
        $this->actingAsAdmin()->getJson('/api/settings/full')->assertOk();
    }

    public function test_the_admin_endpoint_returns_decrypted_values(): void
    {
        Setting::firstOrFail()->forceFill([
            'plex_token' => 'plex-plain',
            'tmdb_api_key_v3' => 'tmdb-plain',
        ])->save();

        $this->actingAsAdmin()
            ->getJson('/api/settings/full')
            ->assertOk()
            ->assertJsonPath('plex_token', 'plex-plain')
            ->assertJsonPath('tmdb_api_key_v3', 'tmdb-plain');
    }

    public function test_ciphertext_never_reaches_the_public_endpoint_either(): void
    {
        Setting::firstOrFail()->forceFill(['plex_token' => 'plex-plain'])->save();

        $body = $this->getJson('/api/settings')->assertOk()->getContent();

        $this->assertStringNotContainsString('plex-plain', $body);
        $this->assertStringNotContainsString((string) $this->raw('plex_token'), $body);
    }

    public function test_the_columns_are_wide_enough_for_ciphertext(): void
    {
        // A long token plus encryption overhead comfortably exceeds varchar(255).
        $long = str_repeat('k', 200);

        Setting::firstOrFail()->forceFill(['plex_token' => $long])->save();

        $this->assertGreaterThan(255, strlen((string) $this->raw('plex_token')));
        $this->assertSame($long, Setting::firstOrFail()->plex_token);
    }
}
