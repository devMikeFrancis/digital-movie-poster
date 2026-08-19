<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TheaterNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_plate_style_defaults_to_plain(): void
    {
        // A display already showing its name should look exactly as it did.
        $this->assertSame('plain', Setting::first()->theater_name_style);
    }

    public function test_every_offered_plate_style_is_accepted(): void
    {
        $this->actingAsAdmin();

        foreach (['plain', 'rules', 'marquee', 'plaque', 'neon'] as $style) {
            $this->putJson('/api/settings', $this->validPayload([
                'theater_name_style' => $style,
            ]))->assertOk();

            $this->assertSame($style, Setting::first()->theater_name_style);
        }
    }

    public function test_an_unknown_plate_style_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/settings', $this->validPayload([
            'theater_name_style' => 'holographic',
        ]))->assertStatus(422)->assertJsonValidationErrors('theater_name_style');
    }

    public function test_the_display_can_see_the_plate_style(): void
    {
        $this->getJson('/api/settings')->assertOk()->assertJsonStructure(['theater_name_style']);
    }

    /** @param  array<string, mixed>  $overrides */
    private function validPayload(array $overrides = []): array
    {
        $booleans = [
            'plex_service', 'plex_sync_movies', 'plex_sync_tv', 'plex_show_movie_now_playing',
            'plex_show_tv_now_playing', 'random_order', 'show_mpaa_rating', 'show_audience_rating',
            'show_processing_logos', 'show_dolby_atmos_horizontal', 'show_dolby_atmos_vertical',
            'show_dolby_vision_horizontal', 'show_dolby_vision_vertical', 'show_dts', 'show_dolby_51',
            'show_imax', 'show_auro_3d', 'use_cec_power', 'show_runtime', 'play_theme_music',
            'use_global_prologos', 'use_global_prologos_if_no_poster_prologos', 'poster_fill_screen',
            'show_header_text', 'show_theater_name', 'require_login', 'jellyfin_service',
            'kodi_service', 'show_header_border', 'validate_movie_titles', 'remove_black_bars',
            'show_speaker_config',
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
