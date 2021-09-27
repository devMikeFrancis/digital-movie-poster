<?php

namespace App\Services;

use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;
use App\Services\PlexService;
use App\Models\Poster;
use App\Models\Setting;

class PosterService
{
    private $settings;

    public function __construct()
    {
        $this->settings = Setting::first();
    }

    public function cache()
    {
        if ($this->settings->plex_service) {
            $plexService = new PlexService();
            $json = $plexService->apiCall('/library/sections/1/all');
            $plexService->save($json);
        }
    }

    public function store($request): Poster
    {
        $imdbId = $request->imdb_id;
        $orginalName = Str::slug($request->title);
        $fileName = $orginalName.'.jpg';
        $imageLocation = $request->image;
        $audienceRating = 0;
        $mpaaRating = 'PG';
        $trailerPath = '';

        if ($imdbId) {
            $tmdb = $this->callTMDB($imdbId);
            $fileName = $tmdb['file_name'];
            $imageLocation = $tmdb['image_location'];
            $audienceRating = $tmdb['audience_rating'];
            $mpaaRating = $tmdb['mpaa_rating'];
            $trailerPath = $tmdb['tailer_path'];
        }

        if ($request->image || $imageLocation) {
            $this->saveImage($imageLocation, $fileName);
        }

        $poster = new Poster();
        $poster->file_name = $fileName;
        $poster->name = $orginalName;
        $poster->can_delete = true;
        $poster->imdb_id = $imdbId;
        $poster->audience_rating = $audienceRating;
        $poster->mpaa_rating = $mpaaRating;
        $poster->trailer_path = $trailerPath;
        $poster->save();

        return $poster;
    }

    public function update($request, $id): Poster
    {
        $imdbId = $request->imdb_id;
        $orginalName = Str::slug($request->title);
        $fileName = $orginalName.'.jpg';
        $imageLocation = $request->image;
        $audienceRating = $request->audience_rating;
        $mpaaRating = $request->mpaa_rating;
        $trailerPath = $request->trailer_path;

        if ($imdbId) {
            $tmdb = $this->callTMDB($imdbId);
            $fileName = $tmdb['file_name'];
            $imageLocation = $tmdb['image_location'];
            $audienceRating = $tmdb['audience_rating'];
            $mpaaRating = $tmdb['mpaa_rating'];
            $trailerPath = $tmdb['tailer_path'];
        }

        if ($request->image || $imageLocation) {
            $this->saveImage($imageLocation, $fileName);
        }

        $poster = Poster::findOrFail($id);
        $poster->file_name = $fileName;
        $poster->name = $orginalName;
        $poster->imdb_id = $imdbId;
        $poster->audience_rating = $audienceRating;
        $poster->mpaa_rating = $mpaaRating;
        $poster->trailer_path = $trailerPath;
        $poster->save();

        return $poster;
    }

    public function updateShowInRotation($id, $showInRotation): void
    {
        $poster = Poster::findOrFail($id);
        $poster->show_in_rotation = $showInRotation;
        $poster->save();
    }

    public function delete($id): ?bool
    {
        $poster = Poster::findOrFail($id);

        if (file_exists(storage_path('app/public/posters/').$poster->file_name)) {
            unlink(storage_path('app/public/posters/').$poster->file_name);
        }

        return $poster->delete();
    }

    public function sort($request): void
    {
        $items = $request->items;
        foreach ($items as $item) {
            $poster = Poster::find($item['id']);
            $poster->ordinal = $item['order'];
            $poster->save();
        }
    }

    private function callTMDB($imdbId): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get('https://api.themoviedb.org/3/movie/'.$imdbId.'?api_key='.env('TMDB_API_V3').'&append_to_response=videos,images,release_dates');

        $data = $response->json();

        $orginalName = Str::slug($data['original_title']);
        $fileName = $orginalName.'.jpg';

        $imageLocation = 'https://image.tmdb.org/t/p/original'.$data['poster_path'];

        $audienceRating = $data['vote_average'];

        foreach ($data['release_dates']['results'] as $country) {
            if ($country['iso_3166_1'] === 'US') {
                $mpaaRating = $country['release_dates'][0]['certification'];
            }
        }

        foreach ($data['videos']['results'] as $video) {
            if ($video['type'] === 'Trailer' && $video['official'] === true && $video['site'] === 'YouTube') {
                $trailerPath = $video['key'];
                break;
            }
        }

        return [
            'image_location' => $imageLocation,
            'file_name' => $fileName,
            'mpaa_rating' => $mpaaRating,
            'audience_rating' => $audienceRating,
            'trailer_path' => $trailerPath
        ];
    }

    private function saveImage($imageLocation, $fileName)
    {
        $image = Image::make($imageLocation);
        $image->resize(1400, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        return $image->save(storage_path('app/public/posters/').$fileName, 75, 'jpg');
    }
}
