<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_settings_are_seeded_by_migration_and_readable(): void
    {
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonStructure(['id', 'coming_soon_text', 'now_playing_text']);
    }

    public function test_settings_can_be_updated(): void
    {
        $this->putJson('/api/settings', $this->validPayload(['coming_soon_text' => 'Up Next']))
            ->assertOk()
            ->assertJson(['saved' => 1]);

        $this->assertSame('Up Next', Setting::first()->coming_soon_text);
    }

    public function test_display_options_are_saved(): void
    {
        $this->putJson('/api/settings', $this->validPayload([
            'poster_fill_screen' => true,
            'show_header_text' => false,
            'show_theater_name' => true,
            'theater_name' => 'The Roxy',
            'theater_name_position' => 'top',
        ]))->assertOk();

        $settings = Setting::first();

        $this->assertEquals(1, $settings->poster_fill_screen);
        $this->assertEquals(0, $settings->show_header_text);
        $this->assertEquals(1, $settings->show_theater_name);
        $this->assertSame('The Roxy', $settings->theater_name);
        $this->assertSame('top', $settings->theater_name_position);
    }

    public function test_the_theater_name_position_only_accepts_top_or_bottom(): void
    {
        $this->putJson('/api/settings', $this->validPayload([
            'theater_name_position' => 'sideways',
        ]))->assertStatus(422)->assertJsonValidationErrors('theater_name_position');
    }

    public function test_a_payload_without_a_theater_name_position_falls_back_to_bottom(): void
    {
        // The admin UI always sends it, but leaving it out should land on the
        // sensible option rather than fail the whole save.
        $payload = $this->validPayload();
        unset($payload['theater_name_position']);

        $this->putJson('/api/settings', $payload)->assertOk();

        $this->assertSame('bottom', Setting::first()->theater_name_position);
    }

    public function test_the_display_can_see_the_new_options(): void
    {
        // The display reads the unauthenticated endpoint, so options it needs
        // to render must survive the deny-list.
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonStructure([
                'poster_fill_screen',
                'show_header_text',
                'show_theater_name',
                'theater_name',
                'theater_name_position',
            ]);
    }

    public function test_settings_update_rejects_a_partial_payload(): void
    {
        $this->putJson('/api/settings', ['coming_soon_text' => 'Up Next'])
            ->assertStatus(422);
    }

    public function test_poster_display_speed_has_a_floor(): void
    {
        $this->putJson('/api/settings', $this->validPayload(['poster_display_speed' => 500]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('poster_display_speed');
    }

    /**
     * The settings endpoint validates the whole record, so every request needs
     * a complete payload. Booleans default to false, strings to a usable value.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $booleans = [
            'plex_service', 'plex_sync_movies', 'plex_sync_tv', 'plex_show_movie_now_playing',
            'plex_show_tv_now_playing', 'random_order', 'show_mpaa_rating', 'show_audience_rating',
            'show_processing_logos', 'show_dolby_atmos_horizontal', 'show_dolby_atmos_vertical',
            'show_dolby_vision_horizontal', 'show_dolby_vision_vertical', 'show_dts', 'show_dolby_51',
            'show_imax', 'show_auro_3d', 'use_cec_power', 'show_runtime', 'play_theme_music',
            'use_global_prologos', 'use_global_prologos_if_no_poster_prologos', 'poster_fill_screen',
            'show_header_text', 'show_theater_name',
            'jellyfin_service', 'kodi_service', 'show_header_border', 'validate_movie_titles',
            'remove_black_bars', 'show_speaker_config',
        ];

        return array_merge(
            array_fill_keys($booleans, false),
            [
                'poster_display_speed' => 15000,
                'transition_type' => 'fade',
                'header_font' => 'Inter',
                'header_font_size' => '4rem',
            ],
            $overrides
        );
    }
}
