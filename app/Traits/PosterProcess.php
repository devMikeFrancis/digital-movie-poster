<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;
use App\Models\Poster;

trait PosterProcess
{
    public $settings;
    public $originalName;
    public $fileName;

    /**
     * Saves poster image
     *
     * @param string $mediaTitle The media title
     * @param string $imageLocation URL or path to the image
     * @param string $mediaType movie|tv
     *
     * @return array
     */
    public function saveImage($mediaTitle, $imageLocation, $mediaType = 'movie'): array
    {
        $message = 'Image saved';
        $success = true;

        if (!is_dir(storage_path("app/public/posters"))) {
            mkdir(storage_path("app/public/posters"), 0775, true);
        }

        $orginalName = Str::slug($mediaTitle);
        $fileName = $orginalName.'.webp';
        if (strtolower($mediaType) === 'tv') {
            $fileName = 'tv_'.$fileName;
        }

        try {
            $image = Image::make($imageLocation);
            $image->resize(1400, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $image->save(storage_path('app/public/posters/').$fileName, 70, 'webp');
            $image->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $image->save(storage_path('app/public/posters/_tn_').$fileName, 70, 'webp');
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
        }

        return [
            'success' => $success,
            'file_name' => $fileName,
            'message' => $message
        ];
    }

    /**
     * Save the poster data
     *
     * Required $params[]
     * $params = [
     *    'name' => string movie title,
     *    'file_name' string ['file_name'] returned from saveImage,
     *    'id' => string unique,
     *    'mpaa_rating' => string G|PG|PG-13|R|NC-17|Not Rated|NR,
     *    'tv_rating' => string TV-Y|TV-Y7|TG-G|TV-PG|TV-14|TV-MA|Not Rated|NR,
     *    'audience_rating' => float Scale 1-10 | 0,
     *    'runtime' => integer in minutes
     *    'media_type' => string movie|tv
     * ];
     *
     * @param array $params
     *
     * @return mixed
     */
    public function savePoster($params)
    {
        $whereUpdate = ['object_id' => $params['id'] ];

        $mediaType = isset($params['media_type']) ? strtolower($params['media_type']) : 'movie';

        $update = [
            'name' => $params['name'],
            'file_name' => $params['file_name'],
            'can_delete' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'mpaa_rating' => isset($params['rating']) ? strtoupper($params['rating']) : null,
            'audience_rating' => isset($params['audience_rating']) ? $params['audience_rating'] : null,
            'runtime' => isset($params['runtime']) ? $params['runtime'] : null,
            'media_type' => $mediaType,
            'show_trailer' => false,
        ];

        if ($this->settings->validate_movie_titles && $mediaType === 'movie') {
            $whereUpdate['name'] = $params['name'];
            unset($update['name']);
        }

        $queryResult = Poster::updateOrCreate(
            $whereUpdate,
            $update
        );

        return $queryResult;
    }

    /**
     * Get movie or tv meta data
     *
     * @param string $mediaId imdbid|tv name
     * @param string $type movie|tv
     *
     * @return array
     */
    public function posterMeta($mediaId, $type = 'movie'): array
    {
        if ($type === 'movie') {
            $res = $this->movieEndpoint($mediaId);
            return $this->getMovieData($res);
        }

        if ($type === 'tv') {
            $res = $this->tvEndpoint($mediaId);
            return $this->getTvData($res);
        }
    }

    private function movieEndpoint($imdbId)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/movie/'.$imdbId.'?api_key='.$this->settings->tmdb_api_key_v3.'&append_to_response=videos,images,release_dates');

        return $response->json();
    }

    private function tvEndpoint($imdbId)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/find/'.$imdbId.'?api_key='.$this->settings->tmdb_api_key_v3.'&external_source=imdb_id');

        $items = $response->json();

        return $items['tv_results'];
    }

    private function getMovieData($res): array
    {
        $success = true;
        $message = '';

        if (isset($res['success']) && !$res['success']) {
            $arr['success'] = false;
            $arr['message'] = 'Movie not found';
            return $arr;
        }

        $imageLocation = 'https://image.tmdb.org/t/p/original'.$res['poster_path'];
        $audienceRating = $res['vote_average'];
        $movieRating = $this->getMovieRating($res['release_dates']['results']);
        $trailerId = $this->getMovieTrailerId($res['videos']['results']);

        return [
            'success' => $success,
            'message' => $message,
            'title' => $res['title'],
            'image' => $imageLocation,
            'mpaa_rating' => $movieRating,
            'audience_rating' => $audienceRating,
            'trailer_id' => $trailerId,
            'runtime' => $res['runtime']
        ];
    }

    private function getTvData($res)
    {
        $success = true;
        $message = '';
        $arr = [];

        if (count($res) === 0) {
            $arr['success'] = false;
            $arr['message'] = 'Tv show not found';
            return $arr;
        }

        $topResult = $res[0];

        $tvMeta = $this->getTvMeta($topResult['id']);

        $imageLocation = 'https://image.tmdb.org/t/p/original'.$topResult['poster_path'];
        $audienceRating = $topResult['vote_average'];
        $tvRating = $this->getTvRating($tvMeta['content_ratings']['results']);

        return [
            'success' => $success,
            'message' => $message,
            'title' => $topResult['name'],
            'image' => $imageLocation,
            'mpaa_rating' => $tvRating,
            'trailer_id' => '',
            'audience_rating' => $audienceRating,
            'runtime' => isset($tvMeta['episode_run_time'][0]) ? $tvMeta['episode_run_time'][0] : null
        ];
    }

    private function getTvMeta($tvId)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/tv/'.$tvId.'?api_key='.$this->settings->tmdb_api_key_v3.'&append_to_response=content_ratings');

        return $response->json();
    }

    private function getMovieRating($releaseDates)
    {
        foreach ($releaseDates as $releaseDate) {
            if ($releaseDate['iso_3166_1'] === 'US') {
                return $releaseDate['release_dates'][0]['certification'];
            }
        }
    }

    private function getTvRating($contentRatings)
    {
        foreach ($contentRatings as $contentRating) {
            if ($contentRating['iso_3166_1'] === 'US') {
                return $contentRating['rating'];
            }
        }
    }

    private function getMovieTrailerId($videos)
    {
        foreach ($videos as $video) {
            if (
                $video['type'] === 'Trailer' &&
                $video['official'] === true &&
                $video['site'] === 'YouTube') {
                return $video['key'];
            }
        }
    }
}
