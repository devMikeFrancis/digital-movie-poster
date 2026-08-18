<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

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
            'use_global_prologos', 'use_global_prologos_if_no_poster_prologos', 'show_bottom_text',
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
