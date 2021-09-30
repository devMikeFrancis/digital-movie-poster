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
        $audienceRating = $request->audience_rating;
        $mpaaRating = $request->mpaa_rating;
        $trailerPath = $request->trailer_path;
        $showTrailer = $request->boolean('show_trailer');
        $showRuntime = $request->boolean('show_runtime');
        $showInRotation = $request->boolean('show_in_rotation');
        $runtime = $request->runtime;
        $playThemeMusic = $request->boolean('play_theme_music');
        $themeMusicPath = null;

        if ($imdbId) {
            $tmdb = $this->callTMDB($imdbId);
            $orginalName = Str::slug($tmdb['title']);
            $fileName = $tmdb['file_name'];
            $imageLocation = $tmdb['image_location'];
            $audienceRating = $tmdb['audience_rating'];
            $mpaaRating = $tmdb['mpaa_rating'];
            $trailerPath = $tmdb['trailer_path'];
            $runtime = $tmdb['runtime'];
        }

        if ($request->image || $imageLocation) {
            $this->saveImage($imageLocation, $fileName);
        }

        if ($request->music) {
            $basename = Str::slug(pathinfo($request->file('music')->getClientOriginalName(), PATHINFO_FILENAME));
            $themeMusicPath = $basename.'.'.$request->music->getClientOriginalExtension();
            $stored = $this->saveMusic($request->music, $themeMusicPath);
            if (!$stored) {
                abort('500', 'Theme music not saved');
            }
        }

        $poster = new Poster();
        $poster->file_name = $fileName;
        $poster->name = $orginalName;
        $poster->can_delete = true;
        $poster->imdb_id = $imdbId;
        $poster->audience_rating = $audienceRating;
        $poster->mpaa_rating = $mpaaRating;
        $poster->trailer_path = $trailerPath;
        $poster->show_trailer = $showTrailer;
        $poster->show_runtime = $showRuntime;
        $poster->show_in_rotation = $showInRotation;
        $poster->runtime = $runtime;
        $poster->theme_music_path = $themeMusicPath;
        $poster->play_theme_music = $playThemeMusic;
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
        $showTrailer = $request->boolean('show_trailer');
        $showRuntime = $request->boolean('show_runtime');
        $showInRotation = $request->boolean('show_in_rotation');
        $runtime = $request->runtime;
        $playThemeMusic = $request->boolean('play_theme_music');
        $themeMusicPath = null;

        if ($imdbId) {
            $tmdb = $this->callTMDB($imdbId);
            $orginalName = Str::slug($tmdb['title']);
            $fileName = $tmdb['file_name'];
            $imageLocation = $tmdb['image_location'];
            $audienceRating = $tmdb['audience_rating'];
            $mpaaRating = $tmdb['mpaa_rating'];
            $trailerPath = $tmdb['trailer_path'];
            $runtime = $tmdb['runtime'];
        }

        if ($request->image || $imageLocation) {
            $this->saveImage($imageLocation, $fileName);
        }

        if ($request->music) {
            $basename = Str::slug(pathinfo($request->file('music')->getClientOriginalName(), PATHINFO_FILENAME));
            $themeMusicPath = $basename.'.'.$request->music->getClientOriginalExtension();
            $stored = $this->saveMusic($request->music, $themeMusicPath);
            if (!$stored) {
                abort('500', 'Theme music not saved');
            }
        }

        $poster = Poster::findOrFail($id);
        $poster->file_name = $fileName;
        $poster->name = $orginalName;
        $poster->imdb_id = $imdbId;
        $poster->audience_rating = $audienceRating;
        $poster->mpaa_rating = $mpaaRating;
        $poster->trailer_path = $trailerPath;
        $poster->show_trailer = $showTrailer;
        $poster->show_runtime = $showRuntime;
        $poster->show_in_rotation = $showInRotation;
        $poster->runtime = $runtime;
        $poster->theme_music_path = $themeMusicPath;
        $poster->play_theme_music = $playThemeMusic;
        $poster->save();

        return $poster;
    }

    public function updateShowInRotation($id, $showInRotation): void
    {
        $poster = Poster::findOrFail($id);
        $poster->show_in_rotation = $showInRotation;
        $poster->save();
    }

    public function updateShowTrailer($id, $showTrailer): void
    {
        $poster = Poster::findOrFail($id);
        $poster->show_trailer = $showTrailer;
        $poster->save();
    }

    public function updateSetting($id, $column, $value): void
    {
        $poster = Poster::findOrFail($id);
        $poster->$column = $value;
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

        if (isset($data['success']) && !$data['success']) {
            abort(404, 'Cannot find movie in TMDB.');
        }

        $orginalName = Str::slug($data['title']);
        $fileName = $orginalName.'.jpg';
        $trailerPath = '';

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
            'title' => $data['title'],
            'image_location' => $imageLocation,
            'file_name' => $fileName,
            'mpaa_rating' => $mpaaRating,
            'audience_rating' => $audienceRating,
            'trailer_path' => $trailerPath,
            'runtime' => $data['runtime']
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

    private function saveMusic($file, $fileName)
    {
        $stored = $file->storeAs('music', $fileName);
        return $stored;
    }
}
