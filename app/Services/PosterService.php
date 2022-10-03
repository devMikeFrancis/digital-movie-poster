<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Services\PlexService;
use App\Services\JellyfinService;
use App\Services\KodiService;
use App\Models\Poster;
use App\Models\Setting;
use App\Traits\PosterProcess;

class PosterService
{
    use PosterProcess;

    public function __construct()
    {
        $this->settings = Setting::first();
    }

    public function cache()
    {
        if ($this->settings->plex_service) {
            $plexService = new PlexService();
            $plexService->syncMovies();
        }

        if ($this->settings->jellyfin_service) {
            $jellyfinService = new JellyfinService();
            $jellyfinService->syncMovies();
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

        $data = $request->validated();

        if ($data['imdb_id']) {
            $tmdb = $this->posterMeta($data['imdb_id'], $data['media_type']);
            if ($tmdb['success']) {
                $data['name'] = $tmdb['title'];
                $data['audience_rating'] = $tmdb['audience_rating'];
                $data['mpaa_rating'] = $tmdb['mpaa_rating'];
                $data['trailer_path'] = $tmdb['trailer_id'];
                $data['runtime'] = $tmdb['runtime'];
                $image = $tmdb['image'];
            }
        }

        if ($image) {
            $savedImage = $this->saveImage($data['name'], $image, $data['media_type']);
            $data['file_name'] = $savedImage['file_name'];
        }

        if (isset($data['music'])) {
            $data['theme_music_path'] = $this->saveMusic($request);
        }

        return Poster::create($data);
    }

    public function update($request, Poster $poster): Poster
    {
        $image = $request->image;

        $data = $request->validated();

        if ($data['imdb_id']) {
            $tmdb = $this->posterMeta($data['imdb_id'], $data['media_type']);
            if ($tmdb['success']) {
                $data['name'] = $tmdb['title'];
                $data['audience_rating'] = $tmdb['audience_rating'];
                $data['mpaa_rating'] = $tmdb['mpaa_rating'];
                $data['trailer_path'] = $tmdb['trailer_id'];
                $data['runtime'] = $tmdb['runtime'];
                $image = $tmdb['image'];
            }
        }

        if ($image) {
            $savedImage = $this->saveImage($data['name'], $image, $data['media_type']);
            $data['file_name'] = $savedImage['file_name'];
        }

        if (isset($data['music'])) {
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
