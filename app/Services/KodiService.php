<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Poster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class KodiService
{
    private $settings;

    public function __construct()
    {
        $this->settings = Setting::first();
    }

    /**
     * Make Kodi API calls to media server
     *
     * @param string $request
     *
     * @return json
     */
    public function apiCall($jsonRpc)
    {
        $request = 'http://'.$this->settings->kodi_url.':'.$this->settings->kodi_port.'/jsonrpc?request='.$jsonRpc;

        if (strlen($this->settings->kodi_username) && strlen($this->settings->kodi_password)) {
            $response = Http::withBasicAuth($this->settings->kodi_username, $this->settings->kodi_password)->get($request);
        } else {
            $response = Http::get($request);
        }

        return $response->json();
    }

    public function syncMovies($page = 0)
    {
        $limit = 20;
        $start = $page * $limit;
        $end = $limit * ($page+1);

        $jsonRpc = '{"jsonrpc": "2.0", "method": "VideoLibrary.GetMovies", "params": {"limits": { "start" : '.$start.', "end": '.$end.' }, "properties" : ["art", "rating", "mpaa", "runtime"], "sort": { "order": "ascending", "method": "label", "ignorearticle": true } }, "id": "libMovies"}';

        $json = $this->apiCall($jsonRpc);

        if (isset($json['result']) && isset($json['result']['movies'])) {
            if (count($json['result']['movies']) > 0) {
                $movies = $json['result']['movies'];
                $this->processMovies($movies);

                if ($end < $json['result']['limits']['total']) {
                    $page = $page+1;
                    $this->syncMovies($page);
                }
            }
        }

        return ['success' => true];
    }

    public function processMovies($movies)
    {
        if (!is_dir(storage_path("app/public/posters"))) {
            mkdir(storage_path("app/public/posters"), 0775, true);
        }
        foreach ($movies as $movie) {
            $orginalName = Str::slug($movie['label']);
            $fileName = $orginalName.'.jpg';

            if (isset($movie['art']) && isset($movie['art']['poster']) && $movie['art']['poster']) {
                $imageUrl = urldecode(str_replace('image://', '', rtrim($movie['art']['poster'], '/')));
                $image = Image::make($imageUrl);
                $image->resize(1400, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/').$fileName, 75, 'jpg');
                $image->resize(200, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/public/posters/_tn_').$fileName, 65, 'jpg');
            }

            Poster::updateOrCreate(
                ['object_id' => 'kodi-'.$movie['movieid'] ],
                [
                    'object_id' => 'kodi-'.$movie['movieid'],
                    'name' => $orginalName,
                    'file_name' => $fileName,
                    'can_delete' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'mpaa_rating' => isset($movie['mpaa']) ? str_replace('Rated ', '', $movie['mpaa']) : null,
                    'audience_rating' => isset($movie['rating']) ? $movie['rating'] : 0,
                    'runtime' => is_numeric($movie['runtime']) ? $movie['runtime']/60 : null
                ]
            );
        }
    }

    public function nowPlaying()
    {
        $jsonRpc = '[{"jsonrpc":"2.0","method":"Player.GetItem","params":{"properties":["title","rating","mpaa","runtime"],"playerid":1},"id":"VideoGetItem"},{"jsonrpc":"2.0","id":1,"method":"Player.GetItem","params":{"playerid":1,"properties":["art"]}}]';

        $json = $this->apiCAll($jsonRpc);

        return $json;
    }
}
