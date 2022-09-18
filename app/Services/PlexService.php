<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Poster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use App\Interfaces\MovieSyncInterface;

class PlexService implements MovieSyncInterface
{
    private $plexIpAddress = '';
    private $plexToken = '';
    private $settings;

    public function __construct()
    {
        $this->settings = Setting::first();

        $this->plexIpAddress = $this->settings->plex_ip_address;
        $this->plexToken = $this->settings->plex_token;
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
        if (!is_dir(storage_path("app/public/posters"))) {
            mkdir(storage_path("app/public/posters"), 0775, true);
        }
        foreach ($movies as $movie) {
            if ($movie['type'] === 'movie') {
                $imageUrl = 'http://'.$this->plexIpAddress.':32400'.$movie['thumb'].'?X-Plex-Token='.$this->plexToken;

                $orginalName = Str::slug($movie['title']);
                $fileName = $orginalName.'.webp';

                $image = Image::make($imageUrl);

                $image->resize(1400, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/').$fileName, 70, 'webp');

                $image->resize(200, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/_tn_').$fileName, 70, 'webp');

                $whereUpdate = ['object_id' => $movie['key'] ];

                $update = [
                    'name' => $orginalName,
                    'file_name' => $fileName,
                    'can_delete' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'mpaa_rating' => isset($movie['contentRating']) ? $movie['contentRating'] : null,
                    'audience_rating' => isset($movie['audienceRating']) ? $movie['audienceRating'] : 0,
                    'runtime' => is_numeric($movie['duration']) ? $movie['duration']/1000/60 : null
                ];

                if ($this->settings->validate_movie_titles) {
                    $whereUpdate['name'] = $orginalName;
                    unset($update['name']);
                }

                Poster::updateOrCreate(
                    $whereUpdate,
                    $update
                );
            }
        }
    }
}
