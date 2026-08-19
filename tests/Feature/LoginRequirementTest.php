<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Whether the admin screens ask for a login is a setting now, so an operator
 * can change it without editing .env on the device.
 */
class LoginRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_login_is_required_by_default(): void
    {
        $this->assertTrue(Setting::first()->require_login);
        $this->assertTrue(AdminAccess::loginRequired());

        $this->getJson('/api/settings/full')->assertUnauthorized();
    }

    public function test_turning_it_off_opens_the_admin_endpoints(): void
    {
        Setting::first()->update(['require_login' => false]);
        AdminAccess::forget();

        $this->getJson('/api/settings/full')->assertOk();
    }

    public function test_turning_it_back_on_closes_them_again(): void
    {
        Setting::first()->update(['require_login' => false]);
        AdminAccess::forget();
        $this->getJson('/api/settings/full')->assertOk();

        Setting::first()->update(['require_login' => true]);
        AdminAccess::forget();

        $this->getJson('/api/settings/full')->assertUnauthorized();
    }

    public function test_saving_the_setting_takes_effect_without_a_restart(): void
    {
        // The resolver remembers its answer for the request, so saving has to
        // forget it - otherwise the change appeared to do nothing until the
        // next one.
        $this->actingAsAdmin();

        $this->putJson('/api/settings', $this->validPayload(['require_login' => false]))
            ->assertOk();

        $this->assertFalse(AdminAccess::loginRequired());
    }

    public function test_the_setting_beats_the_env_value(): void
    {
        // DMP_REQUIRE_LOGIN ships set in .env.example, so if it won the toggle
        // would quietly do nothing on every existing install.
        config(['dmp.auth.required' => true]);
        Setting::first()->update(['require_login' => false]);
        AdminAccess::forget();

        $this->assertFalse(AdminAccess::loginRequired());
    }

    public function test_it_falls_back_to_requiring_a_login_when_there_is_nothing_to_read(): void
    {
        // A database problem should not quietly unlock the device.
        Setting::query()->delete();
        AdminAccess::forget();
        config(['dmp.auth.required' => true]);

        $this->assertTrue(AdminAccess::loginRequired());
    }

    public function test_the_status_endpoint_reports_the_setting(): void
    {
        Setting::first()->update(['require_login' => false]);
        AdminAccess::forget();

        $this->getJson('/api/auth/status')->assertOk()->assertJson(['required' => false]);
    }

    public function test_the_display_endpoints_are_open_either_way(): void
    {
        foreach ([true, false] as $required) {
            Setting::first()->update(['require_login' => $required]);
            AdminAccess::forget();

            $this->getJson('/api/settings')->assertOk();
            $this->getJson('/api/posters')->assertOk();
        }
    }

    /** @param  array<string, mixed>  $overrides */
    private function validPayload(array $overrides = []): array
    {
        $booleans = [
            'plex_service', 'plex_sync_movies', 'plex_sync_tv', 'plex_show_movie_now_playing',
            'plex_show_tv_now_playing', 'random_order', 'show_mpaa_rating', 'show_audience_rating',
            'show_processing_logos', 'show_dolby_atmos_vertical',
            'show_dolby_vision_vertical', 'show_dts', 'show_dolby_51',
            'show_imax', 'show_auro_3d', 'use_cec_power', 'show_runtime', 'play_theme_music',
            'use_global_prologos', 'use_global_prologos_if_no_poster_prologos', 'poster_fill_screen',
            'show_header_text', 'show_theater_name', 'require_login',
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
