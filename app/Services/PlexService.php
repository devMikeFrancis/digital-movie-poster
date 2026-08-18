<?php

namespace App\Services;

use App\Interfaces\MovieSyncInterface;
use App\Models\Setting;
use App\Traits\PosterProcess;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class PlexService implements MovieSyncInterface
{
    use PosterProcess;

    private $plexIpAddress = '';

    private $plexToken = '';

    public function __construct()
    {
        $this->setSettings();
        $this->plexIpAddress = $this->settings->plex_ip_address;
        $this->plexToken = $this->settings->plex_token;
    }

    public function setSettings()
    {
        $this->settings = Setting::first();
    }

    /**
     * Make Plex API calls to media server
     *
     * @param  string  $path  /path/resource
     * @param  string  $method  get|post
     * @param  array  $params
     * @return json
     */
    public function apiCall($path, $method = 'GET', $params = [])
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('http://'.$this->plexIpAddress.':32400'.$path.'?X-Plex-Token='.$this->plexToken);

        return $response->json();
    }

    /**
     * Current Plex session, normalised for the display.
     *
     * This used to be called from the browser with the Plex token in the query
     * string. It runs here now so the token never leaves the server; "poster"
     * points at our own proxy rather than at Plex directly.
     *
     * @return array<string, mixed>
     */
    public function nowPlaying(): array
    {
        $json = $this->apiCall('/status/sessions/');

        if ((int) ($json['MediaContainer']['size'] ?? 0) < 1) {
            return ['playing' => false];
        }

        $item = $json['MediaContainer']['Metadata'][0];
        $type = $item['type'] ?? 'movie';

        $thumb = in_array($type, ['show', 'episode'], true)
            ? ($item['grandparentThumb'] ?? $item['thumb'] ?? null)
            : ($item['thumb'] ?? null);

        return [
            'playing' => ($item['Player']['state'] ?? 'playing') !== 'stopped',
            'mediaType' => $type,
            'title' => $item['title'] ?? null,
            'contentRating' => $item['contentRating'] ?? null,
            'audienceRating' => $item['audienceRating'] ?? null,
            'duration' => isset($item['duration']) && is_numeric($item['duration'])
                ? (int) round($item['duration'] / 1000 / 60)
                : null,
            'posterKey' => $thumb,
        ];
    }

    /**
     * Fetch artwork from Plex so the browser never sees the token.
     *
     * The host comes from settings, never from the request, so this cannot be
     * pointed at another server. The key is still constrained to the Plex
     * library namespace to keep it from walking the rest of the API.
     */
    public function fetchPoster(string $key): Response
    {
        if (! preg_match('#^/library/[A-Za-z0-9/_.-]+$#', $key) || str_contains($key, '..')) {
            throw new InvalidArgumentException('Unsupported Plex artwork path.');
        }

        return Http::timeout(15)->get(
            'http://'.$this->plexIpAddress.':32400'.$key.'?X-Plex-Token='.$this->plexToken
        );
    }

    public function getSections()
    {
        $sections = [];
        $json = $this->apiCall('/library/sections/all');

        if (! isset($json['MediaContainer']['Directory'])) {
            throw new \RuntimeException('Plex did not return a library list.');
        }

        $plexSections = $json['MediaContainer']['Directory'];

        foreach ($plexSections as $plexSection) {
            $sections[] = [
                'key' => $plexSection['key'],
                'title' => $plexSection['title'],
                'type' => $plexSection['type'],
            ];
        }

        return $sections;
    }

    public function syncMedia()
    {
        if ($this->settings->plex_sync_movies) {
            $this->syncMovies($this->settings->plex_movie_sections);
        }
        if ($this->settings->plex_sync_tv) {
            $this->syncTv($this->settings->plex_tv_sections);
        }
    }

    private function syncMovies($sections)
    {
        foreach ($sections as $section) {
            $json = $this->apiCall('/library/sections/'.$section.'/all');
            $medias = $json['MediaContainer']['Metadata'];

            foreach ($medias as $media) {
                if ($media['type'] === 'movie') {
                    $imageUrl = 'http://'.$this->plexIpAddress.':32400'.$media['thumb'].'?X-Plex-Token='.$this->plexToken;

                    $savedImage = $this->saveImage($media['title'], $imageUrl);

                    $params = [
                        'media_type' => 'movie',
                        'name' => $media['title'],
                        'file_name' => $savedImage['file_name'],
                        'id' => $media['key'],
                        'rating' => isset($media['contentRating']) ? $media['contentRating'] : null,
                        'audience_rating' => isset($media['audienceRating']) ? $media['audienceRating'] : 0,
                        'runtime' => is_numeric($media['duration']) ? $media['duration'] / 1000 / 60 : null,
                    ];

                    $this->savePoster($params);
                }
            }
        }
    }

    private function syncTv($sections)
    {
        foreach ($sections as $section) {
            $json = $this->apiCall('/library/sections/'.$section.'/all');
            $shows = $json['MediaContainer']['Metadata'];
            foreach ($shows as $media) {
                if ($media['type'] === 'show') {
                    $imageUrl = 'http://'.$this->plexIpAddress.':32400'.$media['thumb'].'?X-Plex-Token='.$this->plexToken;

                    $savedImage = $this->saveImage($media['title'], $imageUrl);

                    $params = [
                        'media_type' => 'tv',
                        'name' => $media['title'],
                        'file_name' => $savedImage['file_name'],
                        'id' => $media['key'],
                        'rating' => isset($media['contentRating']) ? $media['contentRating'] : null,
                        'audience_rating' => isset($media['audienceRating']) ? $media['audienceRating'] : 0,
                    ];

                    $this->savePoster($params);
                }
            }
        }
    }
}
