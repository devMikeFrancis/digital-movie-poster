<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Poster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class PlexService
{
    private $plexIpAddress = '';
    private $plexToken = '';

    public function __construct()
    {
        $settings = Setting::first();

        $this->plexIpAddress = $settings->plex_ip_address;
        $this->plexToken = $settings->plex_token;
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

    public function processMovie($movies)
    {
        foreach ($movies as $movie) {
            if ($movie['type'] === 'movie') {
                $imageUrl = 'http://'.$this->plexIpAddress.':32400'.$movie['thumb'].'?X-Plex-Token='.$this->plexToken;

                $orginalName = Str::slug($movie['title']);
                $fileName = $orginalName.'.jpg';

                $image = Image::make($imageUrl);
                $image->resize(1400, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/').$fileName, 75, 'jpg');

                Poster::updateOrCreate(
                    ['object_id' => $movie['key'] ],
                    [
                        'object_id' => $movie['key'],
                        'name' => $orginalName,
                        'file_name' => $fileName,
                        'can_delete' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'mpaa_rating' => isset($movie['contentRating']) ? $movie['contentRating'] : null,
                        'audience_rating' => isset($movie['audienceRating']) ? $movie['audienceRating'] : 0,
                        'runtime' => is_numeric($movie['duration']) ? $movie['duration']/1000/60 : null
                    ]
                );
            }
        }
    }

    public function save($data)
    {
        $movies = $data['MediaContainer']['Metadata'];
        $this->processMovie($movies);
    }
}
