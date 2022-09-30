<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use App\Interfaces\MovieSyncInterface;
use App\Traits\PosterProcess;

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
     * @param string $path /path/resource
     * @param string $method get|post
     * @param array $params
     *
     * @return json
     */
    public function apiCall($path, $method = 'GET', $params = [])
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('http://'.$this->plexIpAddress.':32400'.$path.'?X-Plex-Token='.$this->plexToken);

        return $response->json();
    }

    public function syncMovies()
    {
        $json = $this->apiCall('/library/sections/1/all');
        $movies = $json['MediaContainer']['Metadata'];
        $this->processMovies($movies);
    }

    public function processMovies($movies)
    {
        foreach ($movies as $movie) {
            if ($movie['type'] === 'movie') {
                $imageUrl = 'http://'.$this->plexIpAddress.':32400'.$movie['thumb'].'?X-Plex-Token='.$this->plexToken;

                $savedImage = $this->saveImage($movie['title'], $imageUrl);

                $params = [
                    'name' => $movie['title'],
                    'file_name' => $savedImage['file_name'],
                    'id' => $movie['key'],
                    'mpaa_rating' => isset($movie['contentRating']) ? $movie['contentRating'] : null,
                    'audience_rating' => isset($movie['audienceRating']) ? $movie['audienceRating'] : 0,
                    'runtime' => is_numeric($movie['duration']) ? $movie['duration']/1000/60 : null
                ];

                $this->savePoster($params);
            }
        }
    }
}
