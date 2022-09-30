<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use App\Interfaces\MovieSyncInterface;
use App\Traits\PosterProcess;

class KodiService implements MovieSyncInterface
{
    use PosterProcess;

    public function __construct()
    {
        $this->setSettings();
    }

    public function setSettings()
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
    public function apiCall($jsonRpc, $method = 'GET', $params = [])
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
            if (isset($movie['art']) && isset($movie['art']['poster']) && $movie['art']['poster']) {
                $imageUrl = urldecode(str_replace('image://', '', rtrim($movie['art']['poster'], '/')));

                $savedImage = $this->saveImage($movie['label'], $imageUrl);

                $params = [
                    'name' => $movie['label'],
                    'file_name' => $savedImage['file_name'],
                    'id' => 'kodi-'.$movie['movieid'],
                    'mpaa_rating' => isset($movie['mpaa']) ? str_replace('Rated ', '', $movie['mpaa']) : null,
                    'audience_rating' => isset($movie['rating']) ? $movie['rating'] : 0,
                    'runtime' => is_numeric($movie['runtime']) ? $movie['runtime']/60 : null
                ];

                $this->savePoster($params);
            }
        }
    }

    public function nowPlaying()
    {
        $jsonRpc = '[{"jsonrpc":"2.0","method":"Player.GetItem","params":{"properties":["title","rating","mpaa","runtime"],"playerid":1},"id":"VideoGetItem"},{"jsonrpc":"2.0","id":1,"method":"Player.GetItem","params":{"playerid":1,"properties":["art"]}}]';

        $json = $this->apiCAll($jsonRpc);

        return $json;
    }
}
