<?php

namespace App\Traits;

use App\Models\Poster;
use App\Services\TmdbService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

trait PosterProcess
{
    public $settings;

    public $originalName;

    public $fileName;

    /**
     * Saves poster image
     *
     * @param  string  $mediaTitle  The media title
     * @param  string  $imageLocation  URL or path to the image
     * @param  string  $mediaType  movie|tv
     */
    public function saveImage($mediaTitle, $imageLocation, $mediaType = 'movie'): array
    {
        $message = 'Image saved';
        $success = true;

        $directory = storage_path('app/public/posters');
        File::ensureDirectoryExists($directory, 0775);

        $originalName = Str::slug($mediaTitle);
        $fileName = $originalName.'.webp';
        if (strtolower($mediaType) === 'tv') {
            $fileName = 'tv_'.$fileName;
        }

        try {
            $image = Image::decode($this->readImageSource($imageLocation));
            $encoder = new WebpEncoder(quality: 70);

            $image->scale(width: 1400);
            $image->encode($encoder)->save($directory.'/'.$fileName);

            $image->scale(width: 200);
            $image->encode($encoder)->save($directory.'/_tn_'.$fileName);
        } catch (\Throwable $e) {
            $success = false;
            $message = $e->getMessage();
            Log::warning('Could not save poster image for "'.$mediaTitle.'": '.$message);
        }

        return [
            'success' => $success,
            'file_name' => $fileName,
            'message' => $message,
        ];
    }

    /**
     * Resolve an image source into something Intervention Image v4 can decode.
     *
     * Version 2 accepted remote URLs directly; version 4 does not, so remote
     * sources are fetched here and handed over as raw bytes instead.
     *
     * @param  mixed  $imageLocation  URL, local path, or uploaded file
     * @return mixed
     */
    private function readImageSource($imageLocation)
    {
        if (is_string($imageLocation) && Str::startsWith($imageLocation, ['http://', 'https://'])) {
            $response = Http::timeout(30)->get($imageLocation);

            if (! $response->successful()) {
                throw new \RuntimeException('Could not download image ('.$response->status().'): '.$imageLocation);
            }

            return $response->body();
        }

        return $imageLocation;
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
     * @param  array  $params
     * @return mixed
     */
    public function savePoster($params)
    {
        $whereUpdate = ['object_id' => $params['id']];

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
     * Get movie or tv meta data from TMDB.
     *
     * The HTTP client, response shaping and rating extraction now live in
     * TmdbService, so the poster editor's search and "fetch media" actions and
     * this save-time lookup all agree about what a title looks like.
     *
     * @param  string  $mediaId  IMDB id
     * @param  string  $type  movie|tv
     * @return array<string, mixed>
     */
    public function posterMeta($mediaId, $type = 'movie'): array
    {
        try {
            $details = app(TmdbService::class)->detailsByImdbId($mediaId, $type);
        } catch (\Throwable $e) {
            Log::info('TMDB lookup failed for "'.$mediaId.'": '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => '',
            'title' => $details['title'],
            'image' => $details['poster_url'],
            'mpaa_rating' => $details['mpaa_rating'],
            'audience_rating' => $details['audience_rating'],
            'trailer_id' => $details['trailer_id'],
            'runtime' => $details['runtime'],
        ];
    }
}
