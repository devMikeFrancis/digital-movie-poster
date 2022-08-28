<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Poster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class JellyfinService
{
    private $jellyfinIpAddress = '';
    private $jellyfinToken = '';

    public function __construct()
    {
        $settings = Setting::first();

        $this->jellyfinIpAddress = $settings->jellyfin_ip_address;
        $this->jellyfinToken = $settings->jellyfin_token;
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

    public function saveMovies($data)
    {
        //$movies = $data;
        //$this->processMovies($movies);
    }

    public function processMovies($movies)
    {
        /*
        NowPlayingItem
        ->Id
        ->Name
        ->CriticRating
        ->CommunityRating
        ->RunTimeTicks
        ->Type = “Movie”
        ->OfficialRating
        */

        if (!is_dir(storage_path("app/public/posters"))) {
            mkdir(storage_path("app/public/posters"), 0775, true);
        }
        foreach ($movies as $movie) {
            if ($movie['Type'] === 'Movie') {
                $imageUrl = 'http://'.$this->jellyfinIpAddress.':8096/Items/'.$movie['Id'].'/Images/Primary';

                $orginalName = Str::slug($movie['Name']);
                $fileName = $orginalName.'.jpg';

                $image = Image::make($imageUrl);
                $image->resize(1400, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/').$fileName, 75, 'jpg');
                $image->resize(200, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/_tn_').$fileName, 65, 'jpg');

                Poster::updateOrCreate(
                    ['object_id' => $movie['Id'] ],
                    [
                        'object_id' => $movie['Id'],
                        'name' => $orginalName,
                        'file_name' => $fileName,
                        'can_delete' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'mpaa_rating' => isset($movie['OfficialRating']) ? $movie['OfficialRating'] : null,
                        'audience_rating' => isset($movie['CommunityRating']) ? $movie['CommunityRating'] : 0,
                        'runtime' => is_numeric($movie['RunTimeTicks']) ? $movie['RunTimeTicks']/1000/60 : null
                    ]
                );
            }
        }
    }
}
