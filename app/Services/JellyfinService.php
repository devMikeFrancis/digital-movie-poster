<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use App\Interfaces\MovieSyncInterface;
use App\Traits\PosterProcess;

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
        ])->get('http://'.$this->jellyfinIpAddress.':8096'.$path.'?api_key='.$this->jellyfinToken);

        return $response->json();
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
                    'runtime' => is_numeric($movie['RunTimeTicks']) ? $movie['RunTimeTicks']/1000/60 : null
                ];

                $this->savePoster($params);
            }
        }
    }
}
