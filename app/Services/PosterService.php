<?php

namespace App\Services;

use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;
use App\Services\PlexService;
use App\Services\JellyfinService;
use App\Services\KodiService;
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
            $plexService->saveMovies($json);
        }

        if ($this->settings->jellyfin_service) {
            $jellyfinService = new JellyfinService();
            $json = $jellyfinService->apiCall('/Movies');
            $jellyfinService->saveMovies($json);
        }

        if ($this->settings->kodi_service) {
            $kodiService = new KodiService();
            $kodiService->syncMovies();
        }

        return ['success' => true];
    }

    public function store($request): Poster
    {
        $image = $request->image;

        $data = $request->formatted();

        if ($request->imdb_id) {
            $tmdb = $this->callTMDB($request->imdb_id);
            $data['name'] = $tmdb['title'];
            $data['file_name'] = $tmdb['file_name'];
            $data['audience_rating'] = $tmdb['audience_rating'];
            $data['mpaa_rating'] = $tmdb['mpaa_rating'];
            $data['trailer_path'] = $tmdb['trailer_path'];
            $data['runtime'] = $tmdb['runtime'];
            $image = $tmdb['image_location'];
        }

        if ($image) {
            $this->saveImage($image, $data['file_name']);
        }

        if ($request->music) {
            $data['theme_music_path'] = $this->saveMusic($request, $data['file_name']);
        }

        return Poster::create($data);
    }

    public function update($request, Poster $poster): Poster
    {
        $image = $request->image;

        $data = $request->formatted();

        if ($request->imdb_id) {
            $tmdb = $this->callTMDB($request->imdb_id);
            $data['name'] = $tmdb['title'];
            $data['file_name'] = $tmdb['file_name'];
            $data['audience_rating'] = $tmdb['audience_rating'];
            $data['mpaa_rating'] = $tmdb['mpaa_rating'];
            $data['trailer_path'] = $tmdb['trailer_path'];
            $data['runtime'] = $tmdb['runtime'];
            $image = $tmdb['image_location'];
        }

        if ($image) {
            $this->saveImage($image, $data['file_name']);
        }

        if ($request->music) {
            $data['theme_music_path'] = $this->saveMusic($request);
        }

        $poster->update($data);

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

        if (file_exists(storage_path('app/public/posters/_tn_').$poster->file_name)) {
            unlink(storage_path('app/public/posters/_tn_').$poster->file_name);
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
        ])->get('https://api.themoviedb.org/3/movie/'.$imdbId.'?api_key='.$this->settings->tmdb_api_key_v3.'&append_to_response=videos,images,release_dates');

        $res = $response->json();

        if (isset($res['success']) && !$res['success']) {
            abort(404, 'Cannot find movie in TMDB.');
        }

        $orginalName = Str::slug($res['title']);
        $fileName = $orginalName.'.jpg';
        $trailerPath = '';

        $imageLocation = 'https://image.tmdb.org/t/p/original'.$res['poster_path'];

        $audienceRating = $res['vote_average'];

        foreach ($res['release_dates']['results'] as $country) {
            if ($country['iso_3166_1'] === 'US') {
                $mpaaRating = $country['release_dates'][0]['certification'];
            }
        }

        foreach ($res['videos']['results'] as $video) {
            if ($video['type'] === 'Trailer' && $video['official'] === true && $video['site'] === 'YouTube') {
                $trailerPath = $video['key'];
                break;
            }
        }

        return [
            'title' => Str::slug($res['title']),
            'image_location' => $imageLocation,
            'file_name' => $fileName,
            'mpaa_rating' => $mpaaRating,
            'audience_rating' => $audienceRating,
            'trailer_path' => $trailerPath,
            'runtime' => $res['runtime']
        ];
    }

    private function saveImage($imageLocation, $fileName)
    {
        $image = Image::make($imageLocation);
        $image->resize(1400, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        if (!is_dir(storage_path("app/public/posters"))) {
            mkdir(storage_path("app/public/posters"), 0775, true);
        }

        try {
            $image->save(storage_path('app/public/posters/').$fileName, 75, 'jpg');
            $image->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $image->save(storage_path('app/public/posters/_tn_').$fileName, 65, 'jpg');
        } catch (\Exception $e) {
            // Muting this exception because for some reason some images do not save correctly when using Plex. Rare case.
            // This will allow the cache to continue and the user can fix any rarely missing images.
        }
    }

    private function saveMusic($request)
    {
        if (!is_dir(storage_path("app/public/music"))) {
            mkdir(storage_path("app/public/music"), 0775, true);
        }
        $basename = Str::slug(pathinfo($request->file('music')->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = $basename.'.'.$request->music->getClientOriginalExtension();

        try {
            $request->music->storeAs('music', $fileName);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        return $fileName;
    }
}
