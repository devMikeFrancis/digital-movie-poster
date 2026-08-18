<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client for The Movie Database.
 *
 * DMP identifies titles by their IMDB id, but IMDB has no public API - TMDB is
 * what actually answers, and it accepts IMDB ids as well as its own. Searching
 * therefore means searching TMDB and handing back the IMDB id of whatever the
 * operator picks.
 */
class TmdbService
{
    public function __construct(private ?Setting $settings = null)
    {
        $this->settings = $settings ?: Setting::first();
    }

    public function configured(): bool
    {
        return ! empty($this->settings?->tmdb_api_key_v3);
    }

    /**
     * Titles matching a name, newest and most popular first.
     *
     * Deliberately does not resolve each result's IMDB id: that is one extra
     * request per row, and only the title the operator actually picks needs it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, string $mediaType = 'movie'): array
    {
        $mediaType = $this->normaliseType($mediaType);

        $response = $this->get('/search/'.$mediaType, [
            'query' => $query,
            'include_adult' => 'false',
        ]);

        $results = $response['results'] ?? [];

        return array_values(array_map(
            fn (array $item) => $this->summarise($item, $mediaType),
            array_slice($results, 0, 20)
        ));
    }

    /**
     * Everything needed to fill in the poster form, by TMDB id.
     *
     * @return array<string, mixed>
     */
    public function detailsByTmdbId(int|string $tmdbId, string $mediaType = 'movie'): array
    {
        $mediaType = $this->normaliseType($mediaType);

        $append = $mediaType === 'movie'
            ? 'videos,images,release_dates,external_ids'
            : 'content_ratings,external_ids';

        $item = $this->get('/'.$mediaType.'/'.$tmdbId, ['append_to_response' => $append]);

        if (! isset($item['id'])) {
            throw new RuntimeException('That title could not be found on TMDB.');
        }

        return $this->present($item, $mediaType);
    }

    /**
     * The same, by IMDB id - what the poster form has always been keyed on.
     *
     * @return array<string, mixed>
     */
    public function detailsByImdbId(string $imdbId, string $mediaType = 'movie'): array
    {
        $mediaType = $this->normaliseType($mediaType);

        // /find is the documented way to resolve an external id, and works for
        // both movies and shows.
        $found = $this->get('/find/'.$imdbId, ['external_source' => 'imdb_id']);
        $key = $mediaType === 'movie' ? 'movie_results' : 'tv_results';
        $match = $found[$key][0] ?? null;

        if (! $match) {
            throw new RuntimeException(
                'No '.($mediaType === 'movie' ? 'movie' : 'TV show').' on TMDB has the IMDB id '.$imdbId.'.'
            );
        }

        return $this->detailsByTmdbId($match['id'], $mediaType);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function summarise(array $item, string $mediaType): array
    {
        $title = $item['title'] ?? $item['name'] ?? 'Untitled';
        $date = $item['release_date'] ?? $item['first_air_date'] ?? '';

        return [
            'tmdb_id' => $item['id'] ?? null,
            'title' => $title,
            'year' => $date ? substr($date, 0, 4) : null,
            'media_type' => $mediaType,
            'overview' => $item['overview'] ?? '',
            'audience_rating' => $item['vote_average'] ?? null,
            'thumbnail' => $this->image($item['poster_path'] ?? null, 'w154'),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function present(array $item, string $mediaType): array
    {
        $isMovie = $mediaType === 'movie';

        return [
            'tmdb_id' => $item['id'],
            'imdb_id' => $item['external_ids']['imdb_id'] ?? ($item['imdb_id'] ?? null),
            'media_type' => $mediaType,
            'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
            'year' => substr((string) ($item['release_date'] ?? $item['first_air_date'] ?? ''), 0, 4) ?: null,
            'overview' => $item['overview'] ?? '',
            'audience_rating' => $item['vote_average'] ?? null,
            'runtime' => $isMovie
                ? ($item['runtime'] ?? null)
                : ($item['episode_run_time'][0] ?? null),
            'mpaa_rating' => $isMovie
                ? $this->certification($item['release_dates']['results'] ?? [])
                : $this->contentRating($item['content_ratings']['results'] ?? []),
            'trailer_id' => $isMovie ? $this->trailer($item['videos']['results'] ?? []) : null,
            'poster_url' => $this->image($item['poster_path'] ?? null, 'original'),
            'preview_url' => $this->image($item['poster_path'] ?? null, 'w342'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $releaseDates
     */
    private function certification(array $releaseDates): ?string
    {
        foreach ($releaseDates as $entry) {
            if (($entry['iso_3166_1'] ?? null) !== 'US') {
                continue;
            }

            foreach ($entry['release_dates'] ?? [] as $release) {
                if (! empty($release['certification'])) {
                    return $release['certification'];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $contentRatings
     */
    private function contentRating(array $contentRatings): ?string
    {
        foreach ($contentRatings as $entry) {
            if (($entry['iso_3166_1'] ?? null) === 'US' && ! empty($entry['rating'])) {
                return $entry['rating'];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     */
    private function trailer(array $videos): ?string
    {
        foreach ($videos as $video) {
            if (($video['type'] ?? null) === 'Trailer'
                && ($video['site'] ?? null) === 'YouTube'
                && ($video['official'] ?? false) === true) {
                return $video['key'] ?? null;
            }
        }

        // Fall back to any YouTube trailer rather than returning nothing.
        foreach ($videos as $video) {
            if (($video['type'] ?? null) === 'Trailer' && ($video['site'] ?? null) === 'YouTube') {
                return $video['key'] ?? null;
            }
        }

        return null;
    }

    private function image(?string $path, string $size): ?string
    {
        return $path
            ? rtrim((string) config('dmp.tmdb.image_base_url'), '/').'/'.$size.$path
            : null;
    }

    private function normaliseType(string $mediaType): string
    {
        return in_array(strtolower($mediaType), ['tv', 'show'], true) ? 'tv' : 'movie';
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('No TMDB API key is set. Add one in Settings.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->get(rtrim((string) config('dmp.tmdb.base_url'), '/').$path, $query + ['api_key' => $this->settings->tmdb_api_key_v3]);

        if ($response->status() === 401) {
            throw new RuntimeException('TMDB rejected the API key. Check it in Settings.');
        }

        if ($response->status() === 404) {
            throw new RuntimeException('TMDB has no record of that title.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('TMDB is not responding (HTTP '.$response->status().').');
        }

        return $response->json() ?? [];
    }
}
