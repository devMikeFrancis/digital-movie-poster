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
    public $validMpaaRatings = [
        'G',
        'PG',
        'PG-13',
        'R',
        'NC-17',
        'Not Rated',
        'NR'
    ];

    /**
     * Saves poster image
     *
     * @param string $mediaTitle The media title
     * @param string $imageLocation URL or path to the image
     *
     * @return array
     */
    public function saveImage($mediaTitle, $imageLocation): array
    {
        $message = 'Image saved';
        $success = true;

        if (!is_dir(storage_path("app/public/posters"))) {
            mkdir(storage_path("app/public/posters"), 0775, true);
        }

        $orginalName = Str::slug($mediaTitle);
        $fileName = $orginalName.'.webp';

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
     *    'mpaa_rating' => string G|PG|PG-13|R|NC-17|Not Rated,
     *    'audience_rating' => float Scale 1-10 | 0,
     *    'runtime' => integer in minutes
     * ];
     *
     * @param array $params
     *
     * @return mixed
     */
    public function savePoster($params)
    {
        $whereUpdate = ['object_id' => $params['id'] ];

        $update = [
            'name' => $params['name'],
            'file_name' => $params['file_name'],
            'can_delete' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'mpaa_rating' => $params['mpaa_rating'],
            'audience_rating' => $params['audience_rating'],
            'runtime' => $params['runtime']
        ];

        if ($this->settings->validate_movie_titles) {
            $whereUpdate['name'] = $params['name'];
            unset($update['name']);
        }

        $queryResult = Poster::updateOrCreate(
            $whereUpdate,
            $update
        );

        return $queryResult;
    }

    public function posterMeta($imdbId)
    {
        $success = true;
        $message = '';
        $trailerId = '';

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/movie/'.$imdbId.'?api_key='.$this->settings->tmdb_api_key_v3.'&append_to_response=videos,images,release_dates');

        $res = $response->json();

        if (isset($res['success']) && !$res['success']) {
            $success = false;
            $message = 'Movie not found';
        }

        $imageLocation = 'https://image.tmdb.org/t/p/original'.$res['poster_path'];
        $audienceRating = $res['vote_average'];

        foreach ($res['release_dates']['results'] as $country) {
            if ($country['iso_3166_1'] === 'US') {
                $mpaaRating = $country['release_dates'][0]['certification'];
            }
        }

        foreach ($res['videos']['results'] as $video) {
            if (
                $video['type'] === 'Trailer' &&
                $video['official'] === true &&
                $video['site'] === 'YouTube') {
                $trailerId = $video['key'];
                break;
            }
        }

        return [
            'success' => $success,
            'message' => $message,
            'title' => $res['title'],
            'image' => $imageLocation,
            'mpaa_rating' => $mpaaRating,
            'audience_rating' => $audienceRating,
            'trailer_id' => $trailerId,
            'runtime' => $res['runtime']
        ];
    }
}
