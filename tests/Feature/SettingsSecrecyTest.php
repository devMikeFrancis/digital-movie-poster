<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * GET /api/settings is unauthenticated because the kiosk display polls it and
 * cannot log in. It previously returned the whole settings row, which meant
 * anything that could reach the device could read plex_token, jellyfin_token,
 * kodi_password and the TMDB keys.
 */
class SettingsSecrecyTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETS = [
        'plex_token' => 'plex-secret-value',
        'jellyfin_token' => 'jellyfin-secret-value',
        'kodi_password' => 'kodi-secret-value',
        'kodi_username' => 'kodi-user-value',
        'tmdb_api_key_v3' => 'tmdb-v3-secret-value',
        'plex_ip_address' => '10.9.8.7',
        'jellyfin_ip_address' => '10.9.8.6',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Setting::firstOrFail()->forceFill(self::SECRETS)->save();
    }

    public function test_no_secret_value_appears_in_the_public_settings_response(): void
    {
        $body = $this->getJson('/api/settings')->assertOk()->getContent();

        foreach (self::SECRETS as $field => $value) {
            $this->assertStringNotContainsString(
                $value,
                $body,
                "GET /api/settings leaked the value of {$field}"
            );
        }
    }

    public function test_no_secret_field_name_appears_in_the_public_settings_response(): void
    {
        $payload = $this->getJson('/api/settings')->assertOk()->json();

        foreach (array_keys(self::SECRETS) as $field) {
            $this->assertArrayNotHasKey($field, $payload);
        }
    }

    /**
     * Guards against a future migration adding another credential column: any
     * new settings column whose name looks like a secret has to be added to
     * PublicSettingResource::HIDDEN or this fails.
     */
    public function test_no_credential_shaped_column_is_ever_exposed(): void
    {
        $payload = $this->getJson('/api/settings')->assertOk()->json();

        $suspicious = preg_grep(
            '/token|password|secret|api_?key|username|credential|ip_address/i',
            array_keys($payload)
        );

        $this->assertSame(
            [],
            array_values($suspicious),
            'Credential-shaped fields reached the public settings endpoint. '.
            'Add them to PublicSettingResource::HIDDEN.'
        );
    }

    public function test_the_display_still_receives_what_it_needs(): void
    {
        $payload = $this->getJson('/api/settings')->assertOk()->json();

        foreach ([
            'id', 'coming_soon_text', 'now_playing_text', 'poster_display_speed',
            'transition_type', 'random_order', 'show_runtime', 'show_mpaa_rating',
            'plex_service', 'jellyfin_service', 'kodi_service',
            'plex_show_movie_now_playing', 'plex_show_tv_now_playing',
        ] as $field) {
            $this->assertArrayHasKey($field, $payload, "The display needs {$field}");
        }
    }

    public function test_the_full_endpoint_still_serves_the_admin_ui(): void
    {
        $payload = $this->actingAsAdmin()->getJson('/api/settings/full')->assertOk()->json();

        foreach (self::SECRETS as $field => $value) {
            $this->assertSame($value, $payload[$field]);
        }

        $this->assertCount(count(Schema::getColumnListing('settings')), $payload);
    }

    public function test_the_full_endpoint_is_gated_when_protection_is_on(): void
    {
        config(['dmp.auth.required' => true]);

        $this->getJson('/api/settings/full')->assertUnauthorized();

        // The display endpoint must stay reachable.
        $this->getJson('/api/settings')->assertOk();
    }
}
