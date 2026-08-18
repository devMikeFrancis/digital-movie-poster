<?php

namespace Tests\Feature;

use App\Casts\EncryptedCredential;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The upgrade path for installs that already hold plaintext credentials.
 */
class CredentialEncryptionMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_08_18_000001_encrypt_media_server_credentials.php';

    private function runMigration(string $direction = 'up'): void
    {
        $migration = require base_path(self::MIGRATION);
        $migration->{$direction}();
    }

    /**
     * Writes through the query builder so the cast does not encrypt on the way
     * in - this is what a pre-upgrade database looks like.
     */
    private function seedPlaintext(): array
    {
        $values = [];

        foreach (Setting::ENCRYPTED_CREDENTIALS as $column) {
            $values[$column] = 'plain-'.$column;
        }

        DB::table('settings')->update($values);

        return $values;
    }

    public function test_it_encrypts_credentials_that_were_stored_in_plain_text(): void
    {
        $plaintext = $this->seedPlaintext();

        $this->runMigration();

        foreach ($plaintext as $column => $value) {
            $stored = DB::table('settings')->value($column);

            $this->assertTrue(
                EncryptedCredential::looksEncrypted($stored),
                "{$column} was not encrypted by the migration"
            );
            $this->assertSame($value, Setting::firstOrFail()->{$column});
        }
    }

    public function test_running_it_again_does_not_double_encrypt(): void
    {
        $plaintext = $this->seedPlaintext();

        $this->runMigration();
        $afterFirst = DB::table('settings')->value('plex_token');

        $this->runMigration();
        $afterSecond = DB::table('settings')->value('plex_token');

        $this->assertSame($afterFirst, $afterSecond);
        $this->assertSame($plaintext['plex_token'], Setting::firstOrFail()->plex_token);
    }

    public function test_it_leaves_null_and_empty_credentials_alone(): void
    {
        DB::table('settings')->update(['plex_token' => null, 'jellyfin_token' => '']);

        $this->runMigration();

        $this->assertNull(DB::table('settings')->value('plex_token'));
        $this->assertSame('', DB::table('settings')->value('jellyfin_token'));
    }

    public function test_rolling_back_restores_readable_values(): void
    {
        $plaintext = $this->seedPlaintext();

        $this->runMigration();
        $this->runMigration('down');

        foreach ($plaintext as $column => $value) {
            $this->assertSame(
                $value,
                DB::table('settings')->value($column),
                "{$column} was not decrypted on rollback"
            );
        }
    }

    public function test_it_does_not_disturb_other_settings(): void
    {
        DB::table('settings')->update([
            'coming_soon_text' => 'Up Next',
            'poster_display_speed' => 22000,
            'plex_ip_address' => '10.1.2.3',
        ]);

        $this->seedPlaintext();
        $this->runMigration();

        $settings = Setting::firstOrFail();

        $this->assertSame('Up Next', $settings->coming_soon_text);
        $this->assertSame(22000, (int) $settings->poster_display_speed);
        $this->assertSame('10.1.2.3', $settings->plex_ip_address);
    }
}
