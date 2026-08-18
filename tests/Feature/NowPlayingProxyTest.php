<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The display calls these instead of talking to Plex/Jellyfin itself, so the
 * media-server credentials never reach the browser.
 */
class NowPlayingProxyTest extends TestCase
{
    use RefreshDatabase;

    private function configure(array $overrides = []): void
    {
        Setting::firstOrFail()->forceFill(array_merge([
            'plex_service' => true,
            'plex_ip_address' => '10.0.0.5',
            'plex_token' => 'plex-secret',
            'jellyfin_service' => true,
            'jellyfin_ip_address' => '10.0.0.6',
            'jellyfin_token' => 'jellyfin-secret',
        ], $overrides))->save();
    }

    public function test_plex_session_is_normalised_and_the_token_is_not_returned(): void
    {
        $this->configure();

        Http::fake(['10.0.0.5:32400/status/sessions*' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [[
                    'type' => 'movie',
                    'title' => 'Blade Runner',
                    'contentRating' => 'R',
                    'audienceRating' => 8.6,
                    'duration' => 6720000, // ms -> 112 minutes
                    'thumb' => '/library/metadata/42/thumb/1699999999',
                    'Player' => ['state' => 'playing'],
                ]],
            ],
        ])]);

        $response = $this->getJson('/api/now-playing/plex')->assertOk();

        $response->assertJson([
            'playing' => true,
            'mediaType' => 'movie',
            'contentRating' => 'R',
            'audienceRating' => 8.6,
            'duration' => 112,
        ]);

        $this->assertStringNotContainsString('plex-secret', $response->getContent());
        $this->assertStringNotContainsString('10.0.0.5', $response->getContent());
        $this->assertStringContainsString('/api/now-playing/plex/poster', $response->json('poster'));
    }

    public function test_plex_tv_sessions_use_the_grandparent_thumbnail(): void
    {
        $this->configure();

        Http::fake(['10.0.0.5:32400/status/sessions*' => Http::response([
            'MediaContainer' => [
                'size' => 1,
                'Metadata' => [[
                    'type' => 'episode',
                    'thumb' => '/library/metadata/9/thumb/1',
                    'grandparentThumb' => '/library/metadata/1/thumb/2',
                    'Player' => ['state' => 'playing'],
                ]],
            ],
        ])]);

        $poster = $this->getJson('/api/now-playing/plex')->assertOk()->json('poster');

        $this->assertStringContainsString('/library/metadata/1/thumb/2', urldecode($poster));
    }

    public function test_an_empty_plex_session_list_reports_nothing_playing(): void
    {
        $this->configure();

        Http::fake(['10.0.0.5:32400/status/sessions*' => Http::response(['MediaContainer' => ['size' => 0]])]);

        $this->getJson('/api/now-playing/plex')->assertOk()->assertJson(['playing' => false]);
    }

    public function test_jellyfin_session_is_normalised(): void
    {
        $this->configure();

        Http::fake(['10.0.0.6:8096/Sessions*' => Http::response([
            ['NowPlayingItem' => null],
            [
                'PlayState' => ['IsPaused' => false],
                'NowPlayingItem' => [
                    'Type' => 'Movie',
                    'Name' => 'Arrival',
                    'OfficialRating' => 'PG-13',
                    'CommunityRating' => 7.9,
                    'RunTimeTicks' => 71400000000, // -> 119 minutes
                    'Id' => 'abc123',
                ],
            ],
        ])]);

        $response = $this->getJson('/api/now-playing/jellyfin')->assertOk();

        $response->assertJson([
            'playing' => true,
            'mediaType' => 'movie',
            'contentRating' => 'PG-13',
            'duration' => 119,
        ]);

        $this->assertStringNotContainsString('jellyfin-secret', $response->getContent());
    }

    public function test_a_disabled_service_reports_not_enabled_without_calling_out(): void
    {
        $this->configure(['plex_service' => false]);
        Http::fake();

        $this->getJson('/api/now-playing/plex')
            ->assertOk()
            ->assertJson(['playing' => false, 'enabled' => false]);

        Http::assertNothingSent();
    }

    public function test_an_unreachable_media_server_is_not_an_error(): void
    {
        $this->configure();
        Http::fake(fn () => throw new ConnectionException('refused'));

        $this->getJson('/api/now-playing/plex')
            ->assertOk()
            ->assertJson(['playing' => false, 'reachable' => false]);
    }

    public function test_an_unknown_service_is_rejected(): void
    {
        $this->getJson('/api/now-playing/plexx')->assertNotFound();
    }

    public function test_the_poster_proxy_streams_artwork_without_exposing_the_token(): void
    {
        $this->configure();

        Http::fake(['10.0.0.5:32400/library/metadata/42/thumb*' => Http::response(
            'BINARY-IMAGE-BYTES', 200, ['Content-Type' => 'image/jpeg']
        )]);

        $response = $this->get('/api/now-playing/plex/poster?key=/library/metadata/42/thumb/1699999999');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('BINARY-IMAGE-BYTES', $response->getContent());
    }

    public function test_the_poster_proxy_rejects_a_path_outside_the_plex_library(): void
    {
        $this->configure();
        Http::fake();

        $this->get('/api/now-playing/plex/poster?key=/status/sessions')->assertStatus(422);
        $this->get('/api/now-playing/plex/poster?key=/library/../etc/passwd')->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_poster_proxy_rejects_a_malformed_jellyfin_id(): void
    {
        $this->configure();
        Http::fake();

        $this->get('/api/now-playing/jellyfin/poster?key=../../secrets')->assertStatus(422);

        Http::assertNothingSent();
    }
}
