<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\PosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Title search and lookup for the poster editor.
 *
 * DMP keys posters on IMDB ids, but IMDB has no public API - TMDB answers, and
 * accepts IMDB ids as well as its own.
 */
class TmdbLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::firstOrFail()->forceFill(['tmdb_api_key_v3' => 'test-key'])->save();
    }

    public function test_searching_by_title_returns_pickable_results(): void
    {
        Http::fake(['*/search/movie*' => Http::response(['results' => [
            [
                'id' => 78,
                'title' => 'Blade Runner',
                'release_date' => '1982-06-25',
                'overview' => 'A blade runner must pursue replicants.',
                'vote_average' => 8.1,
                'poster_path' => '/blade.jpg',
            ],
            [
                'id' => 335984,
                'title' => 'Blade Runner 2049',
                'release_date' => '2017-10-04',
                'overview' => 'Thirty years later.',
                'vote_average' => 7.5,
                'poster_path' => '/2049.jpg',
            ],
        ]])]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=blade+runner&media_type=movie')
            ->assertOk();

        $response->assertJsonPath('results.0.title', 'Blade Runner')
            ->assertJsonPath('results.0.year', '1982')
            ->assertJsonPath('results.0.media_type', 'movie')
            ->assertJsonPath('results.0.tmdb_id', 78)
            ->assertJsonPath('results.1.year', '2017');

        // Enough to tell two same-named titles apart at a glance.
        $this->assertStringContainsString('/w154/blade.jpg', $response->json('results.0.thumbnail'));
    }

    public function test_a_search_with_no_matches_is_an_empty_list_not_an_error(): void
    {
        Http::fake(['*/search/movie*' => Http::response(['results' => []])]);

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=nothing+matches+this')
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    public function test_tv_search_reads_the_show_fields(): void
    {
        Http::fake(['*/search/tv*' => Http::response(['results' => [
            ['id' => 1399, 'name' => 'Game of Thrones', 'first_air_date' => '2011-04-17', 'poster_path' => '/got.jpg'],
        ]])]);

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=thrones&media_type=tv')
            ->assertOk()
            ->assertJsonPath('results.0.title', 'Game of Thrones')
            ->assertJsonPath('results.0.year', '2011')
            ->assertJsonPath('results.0.media_type', 'tv');
    }

    public function test_a_search_needs_a_couple_of_characters(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=a')
            ->assertStatus(422)
            ->assertJsonValidationErrors('query');
    }

    public function test_picking_a_result_returns_everything_the_form_needs(): void
    {
        Http::fake(['*/movie/78*' => Http::response([
            'id' => 78,
            'title' => 'Blade Runner',
            'release_date' => '1982-06-25',
            'runtime' => 117,
            'vote_average' => 8.1,
            'poster_path' => '/blade.jpg',
            'external_ids' => ['imdb_id' => 'tt0083658'],
            'release_dates' => ['results' => [
                ['iso_3166_1' => 'GB', 'release_dates' => [['certification' => '15']]],
                ['iso_3166_1' => 'US', 'release_dates' => [['certification' => 'R']]],
            ]],
            'videos' => ['results' => [
                ['type' => 'Teaser', 'site' => 'YouTube', 'official' => true, 'key' => 'teaser'],
                ['type' => 'Trailer', 'site' => 'YouTube', 'official' => true, 'key' => 'the-trailer'],
            ]],
        ])]);

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/title?tmdb_id=78&media_type=movie')
            ->assertOk()
            ->assertJsonPath('title.imdb_id', 'tt0083658')
            ->assertJsonPath('title.title', 'Blade Runner')
            ->assertJsonPath('title.runtime', 117)
            ->assertJsonPath('title.mpaa_rating', 'R')      // US certification, not GB
            ->assertJsonPath('title.trailer_id', 'the-trailer')
            ->assertJsonPath('title.year', '1982');
    }

    public function test_fetching_by_imdb_id_resolves_through_find(): void
    {
        Http::fake([
            '*/find/tt0083658*' => Http::response(['movie_results' => [['id' => 78]]]),
            '*/movie/78*' => Http::response([
                'id' => 78, 'title' => 'Blade Runner', 'poster_path' => '/blade.jpg',
                'external_ids' => ['imdb_id' => 'tt0083658'],
            ]),
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/title?imdb_id=tt0083658&media_type=movie')
            ->assertOk()
            ->assertJsonPath('title.title', 'Blade Runner')
            ->assertJsonPath('title.tmdb_id', 78);
    }

    public function test_an_unknown_imdb_id_says_so_plainly(): void
    {
        Http::fake(['*/find/*' => Http::response(['movie_results' => []])]);

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/title?imdb_id=tt9999999&media_type=movie')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No movie on TMDB has the IMDB id tt9999999.');
    }

    public function test_a_missing_api_key_is_reported_not_a_crash(): void
    {
        Setting::firstOrFail()->forceFill(['tmdb_api_key_v3' => null])->save();
        Http::fake();

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=blade+runner')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No TMDB API key is set. Add one in Settings.');

        Http::assertNothingSent();
    }

    public function test_a_rejected_api_key_is_reported(): void
    {
        Http::fake(['*' => Http::response(['status_message' => 'Invalid API key'], 401)]);

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=blade+runner')
            ->assertStatus(422)
            ->assertJsonPath('message', 'TMDB rejected the API key. Check it in Settings.');
    }

    public function test_an_unreachable_tmdb_is_a_502(): void
    {
        Http::fake(fn () => throw new ConnectionException('refused'));

        $this->actingAsAdmin()
            ->getJson('/api/tmdb/search?query=blade+runner')
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }

    public function test_the_lookup_endpoints_require_authentication(): void
    {
        Http::fake();

        $this->getJson('/api/tmdb/search?query=blade+runner')->assertUnauthorized();
        $this->getJson('/api/tmdb/title?imdb_id=tt0083658')->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_saving_a_poster_still_uses_the_same_lookup(): void
    {
        // The save path was rewired onto TmdbService; this guards that it still
        // populates a poster from an IMDB id.
        Http::fake([
            '*/find/tt0083658*' => Http::response(['movie_results' => [['id' => 78]]]),
            '*/movie/78*' => Http::response([
                'id' => 78, 'title' => 'Blade Runner', 'runtime' => 117, 'vote_average' => 8.1,
                'poster_path' => '/blade.jpg', 'external_ids' => ['imdb_id' => 'tt0083658'],
                'release_dates' => ['results' => [['iso_3166_1' => 'US', 'release_dates' => [['certification' => 'R']]]]],
            ]),
            'image.tmdb.org/*' => Http::response($this->pngBytes(), 200),
        ]);

        $meta = app(PosterService::class)->posterMeta('tt0083658', 'movie');

        $this->assertTrue($meta['success']);
        $this->assertSame('Blade Runner', $meta['title']);
        $this->assertSame('R', $meta['mpaa_rating']);
        $this->assertSame(117, $meta['runtime']);
    }

    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
