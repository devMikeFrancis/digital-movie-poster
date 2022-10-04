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

    public function getSections()
    {
        $sections = [];
        $json = $this->apiCall('/library/sections/all');
        $plexSections = $json['MediaContainer']['Directory'];

        foreach ($plexSections as $plexSection) {
            $sections[] = [
                'key' => $plexSection['key'],
                'title' => $plexSection['title'],
                'type' => $plexSection['type']
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
                        'runtime' => is_numeric($media['duration']) ? $media['duration']/1000/60 : null
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
