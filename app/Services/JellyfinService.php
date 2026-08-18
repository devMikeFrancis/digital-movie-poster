<?php

namespace App\Services;

use App\Interfaces\MovieSyncInterface;
use App\Models\Setting;
use App\Traits\PosterProcess;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class JellyfinService implements MovieSyncInterface
{
    use PosterProcess;

    private $jellyfinIpAddress = '';

    private $jellyfinToken = '';

    public function __construct()
    {
        $this->setSettings();

        $this->jellyfinIpAddress = $this->settings->jellyfin_ip_address;
        $this->jellyfinToken = $this->settings->jellyfin_token;
    }

    public function setSettings()
    {
        $this->settings = Setting::first();
    }

    /**
     * Make Jellyfin API calls to media server
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
        ])->get('http://'.$this->jellyfinIpAddress.':8096'.$path.'?api_key='.$this->jellyfinToken);

        return $response->json();
    }

    /**
     * Current Jellyfin session, normalised for the display.
     *
     * The browser used to poll /Sessions directly with api_key in the query
     * string; that now happens here so the token stays server side.
     *
     * @return array<string, mixed>
     */
    public function nowPlaying(): array
    {
        $sessions = $this->apiCall('/Sessions');

        if (! is_array($sessions)) {
            return ['playing' => false];
        }

        foreach ($sessions as $session) {
            $item = $session['NowPlayingItem'] ?? null;

            if (! $item || ($item['Type'] ?? null) !== 'Movie') {
                continue;
            }

            return [
                'playing' => ! ($session['PlayState']['IsPaused'] ?? false),
                'mediaType' => 'movie',
                'title' => $item['Name'] ?? null,
                'contentRating' => $item['OfficialRating'] ?? null,
                'audienceRating' => $item['CommunityRating'] ?? null,
                'duration' => isset($item['RunTimeTicks']) && is_numeric($item['RunTimeTicks'])
                    ? (int) round($item['RunTimeTicks'] / 10000 / 1000 / 60)
                    : null,
                'posterKey' => $item['Id'] ?? null,
            ];
        }

        return ['playing' => false];
    }

    /**
     * Fetch artwork from Jellyfin. The host comes from settings, and the key is
     * constrained to an item id so it cannot address arbitrary endpoints.
     */
    public function fetchPoster(string $key): Response
    {
        if (! preg_match('#^[A-Za-z0-9-]{1,64}$#', $key)) {
            throw new InvalidArgumentException('Unsupported Jellyfin item id.');
        }

        return Http::timeout(15)->get(
            'http://'.$this->jellyfinIpAddress.':8096/Items/'.$key.'/Images/Primary?api_key='.$this->jellyfinToken
        );
    }

    public function syncMedia()
    {
        $json = $this->apiCall('/Items');
        $movies = $json['Items'];
        $this->processMovies($movies);
    }

    public function processMovies($movies)
    {
        foreach ($movies as $movie) {
            if ($movie['Type'] === 'Movie') {
                $imageUrl = 'http://'.$this->jellyfinIpAddress.':8096/Items/'.$movie['Id'].'/Images/Primary';

                $savedImage = $this->saveImage($movie['Name'], $imageUrl);

                $params = [
                    'name' => $movie['Name'],
                    'file_name' => $savedImage['file_name'],
                    'id' => $movie['Id'],
                    'mpaa_rating' => isset($movie['OfficialRating']) ? $movie['OfficialRating'] : null,
                    'audience_rating' => isset($movie['CommunityRating']) ? $movie['CommunityRating'] : 0,
                    'runtime' => is_numeric($movie['RunTimeTicks']) ? $movie['RunTimeTicks'] / 1000 / 60 : null,
                ];

                $this->savePoster($params);
            }
        }
    }
}
