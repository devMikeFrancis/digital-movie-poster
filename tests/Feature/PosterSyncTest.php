<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\KodiService;
use App\Services\PosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The sync path was the least defended part of the app: the now-playing calls
 * next to it check what came back, and these did not.
 */
class PosterSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_media_server_that_is_down_does_not_stop_the_others(): void
    {
        // Plex is unreachable; Jellyfin answers. Running them in a row with
        // nothing catching anything meant Jellyfin never ran at all.
        Setting::first()->update([
            'plex_service' => true,
            'plex_ip_address' => '10.0.0.1',
            'plex_token' => 'token',
            'plex_sync_movies' => true,
            'plex_movie_sections' => ['1'],
            'jellyfin_service' => true,
            'jellyfin_ip_address' => '10.0.0.2',
            'jellyfin_token' => 'token',
        ]);

        Http::fake([
            '10.0.0.1*' => fn () => throw new ConnectionException('down'),
            '10.0.0.2*' => Http::response(['Items' => []], 200),
        ]);

        $result = (new PosterService)->cache();

        $this->assertSame(['plex'], $result['failed']);
        $this->assertFalse($result['success']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '10.0.0.2'));
    }

    public function test_a_sync_with_nothing_switched_on_succeeds_quietly(): void
    {
        $result = (new PosterService)->cache();

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['failed']);
    }

    public function test_plex_sections_that_were_never_configured_do_not_break_the_sync(): void
    {
        Setting::first()->update([
            'plex_service' => true,
            'plex_ip_address' => '10.0.0.1',
            'plex_token' => 'token',
            'plex_sync_movies' => true,
            'plex_movie_sections' => null,
            'plex_sync_tv' => true,
            'plex_tv_sections' => null,
        ]);

        $result = (new PosterService)->cache();

        $this->assertTrue($result['success'], 'a null section list should be nothing to do');
    }

    public function test_an_empty_plex_section_does_not_break_the_sync(): void
    {
        Setting::first()->update([
            'plex_service' => true,
            'plex_ip_address' => '10.0.0.1',
            'plex_token' => 'token',
            'plex_sync_movies' => true,
            'plex_movie_sections' => ['1'],
        ]);

        // Plex omits Metadata entirely when a section holds nothing.
        Http::fake(['10.0.0.1*' => Http::response(['MediaContainer' => ['size' => 0]], 200)]);

        $result = (new PosterService)->cache();

        $this->assertTrue($result['success']);
    }

    public function test_kodi_can_page_past_the_first_twenty_films(): void
    {
        // The recursive call named a method that does not exist on the class,
        // so a library larger than one page synced its first page and then
        // died. Anything with fewer than twenty films never saw it.
        $this->assertTrue(
            method_exists(KodiService::class, 'syncMedia'),
            'syncMedia is the method the pagination has to call'
        );

        $source = file_get_contents(app_path('Services/KodiService.php'));

        $this->assertStringNotContainsString(
            '$this->syncMovies(',
            $source,
            'KodiService has no syncMovies method; paging must call syncMedia'
        );
    }
}
