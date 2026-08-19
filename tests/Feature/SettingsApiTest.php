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

    public function test_the_fill_scrim_defaults_to_standard_and_accepts_the_named_strengths(): void
    {
        $this->assertSame('standard', Setting::first()->poster_fill_scrim);

        $this->putJson('/api/settings', $this->validPayload([
            'poster_fill_scrim' => 'none',
        ]))->assertOk();

        $this->assertSame('none', Setting::first()->poster_fill_scrim);
    }

    public function test_the_fill_scrim_rejects_anything_else(): void
    {
        $this->putJson('/api/settings', $this->validPayload([
            'poster_fill_scrim' => 'very',
        ]))->assertStatus(422)->assertJsonValidationErrors('poster_fill_scrim');
    }

    public function test_a_payload_without_a_fill_scrim_falls_back_to_standard(): void
    {
        $payload = $this->validPayload();
        unset($payload['poster_fill_scrim']);

        $this->putJson('/api/settings', $payload)->assertOk();

        $this->assertSame('standard', Setting::first()->poster_fill_scrim);
    }

    public function test_the_new_flags_are_real_booleans_for_the_admin_form(): void
    {
        // A checkbox bound with v-model only ticks for a real true. Handing the
        // form a 1 left the box empty while the option was on, and saving that
        // form turned the option off.
        $this->putJson('/api/settings', $this->validPayload([
            'poster_fill_screen' => true,
            'show_header_text' => true,
            'show_theater_name' => true,
        ]))->assertOk();

        $payload = $this->getJson('/api/settings/full')->assertOk()->json();

        foreach (['poster_fill_screen', 'show_header_text', 'show_theater_name'] as $flag) {
            $this->assertTrue($payload[$flag], $flag.' should come back as a real boolean');
            $this->assertIsBool($payload[$flag], $flag.' should come back as a real boolean');
        }
    }

    public function test_every_offered_transition_type_is_accepted(): void
    {
        foreach (['fade', 'crossfade', 'vertical', 'cut'] as $type) {
            $this->putJson('/api/settings', $this->validPayload([
                'transition_type' => $type,
            ]))->assertOk();

            $this->assertSame($type, Setting::first()->transition_type);
        }
    }

    public function test_an_unknown_transition_type_is_rejected(): void
    {
        $this->putJson('/api/settings', $this->validPayload([
            'transition_type' => 'barrel-roll',
        ]))->assertStatus(422)->assertJsonValidationErrors('transition_type');
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
                'poster_fill_scrim',
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
            'show_processing_logos', 'show_dolby_atmos_vertical',
            'show_dolby_vision_vertical', 'show_dts', 'show_dolby_51',
            'show_imax', 'show_auro_3d', 'use_cec_power', 'show_runtime', 'play_theme_music',
            'use_global_prologos', 'use_global_prologos_if_no_poster_prologos', 'poster_fill_screen',
            'show_header_text', 'show_theater_name',
            'jellyfin_service', 'kodi_service', 'validate_movie_titles',
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
