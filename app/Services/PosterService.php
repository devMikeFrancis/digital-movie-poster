<?php

namespace App\Services;

use App\Models\Poster;
use App\Models\Setting;
use App\Traits\PosterProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PosterService
{
    use PosterProcess;

    public function __construct()
    {
        $this->settings = Setting::first();
    }

    /**
     * Pull posters from every media server that is switched on.
     *
     * Each one is run on its own. They used to run in a row with nothing
     * catching anything, so a media server that was switched off at the wall
     * took the whole sync down with it and the ones after it never ran.
     *
     * @return array{success: bool, failed: list<string>}
     */
    public function cache()
    {
        $services = array_filter([
            'plex' => $this->settings->plex_service ? PlexService::class : null,
            'jellyfin' => $this->settings->jellyfin_service ? JellyfinService::class : null,
            'kodi' => $this->settings->kodi_service ? KodiService::class : null,
        ]);

        $failed = [];

        foreach ($services as $name => $class) {
            try {
                (new $class)->syncMedia();
            } catch (\Throwable $e) {
                $failed[] = $name;
                Log::warning('Sync from '.$name.' failed: '.$e->getMessage());
            }
        }

        return ['success' => $failed === [], 'failed' => $failed];
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
            $data['file_name'] = $this->storeImageOrFail($data['name'], $image, $data['media_type']);
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
            $data['file_name'] = $this->storeImageOrFail($data['name'], $image, $data['media_type']);
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

    /**
     * Write the artwork, or stop.
     *
     * saveImage() reports failure rather than throwing, and both callers used
     * to take its file name regardless - so a failed download produced a
     * poster row pointing at a file that was never written, and the operator
     * saw no error at all. The usual cause is a driver whose PHP extension is
     * missing, which fails every single save.
     */
    private function storeImageOrFail(string $name, $image, string $mediaType): string
    {
        $saved = $this->saveImage($name, $image, $mediaType);

        if (! $saved['success']) {
            abort(422, 'The artwork could not be saved: '.$saved['message']);
        }

        return $saved['file_name'];
    }

    private function saveMusic($request)
    {
        $basename = Str::slug(pathinfo($request->file('music')->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = $basename.'.'.$request->music->getClientOriginalExtension();

        try {
            // Must live on the "public" disk: the player loads it from /storage/music/<file>.
            $request->music->storeAs('music', $fileName, 'public');
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

        return $fileName;
    }
}
