<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PosterRequest;
use App\Jobs\SyncPosters;
use App\Services\PosterService;
use App\Services\KodiService;
use App\Http\Resources\PostersCollection;
use App\Http\Resources\PosterResource;
use App\Models\Poster;

class PosterController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Get posters
     *
     * @param Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $posters = $request->show_in_rotation ?
        Poster::where('show_in_rotation', true) :
        Poster::orderBy('ordinal');

        return new PostersCollection(
            $posters->orderBy('ordinal')->orderBy('name')->get()
        );
    }

    /**
     * Get poster
     *
     * @param Poster $poster
     *
     * @return array
     */
    public function show(Poster $poster)
    {
        return new PosterResource($poster);
    }

    /**
     * Re download posters from external service
     *
     * @param PosterService $service
     *
     * @return array
     */
    public function cache()
    {
        SyncPosters::dispatch();

        return ['message' => 'sync job queued'];
    }

    /**
     * Re download posters from external service
     *
     * @param PosterService $service
     *
     * @return array
     */
    public function checkSyncStatus()
    {
        $status = 'clear';
        $jobCount = \DB::table('jobs')->where('payload', 'like', '%SyncPosters%')->count();
        if ($jobCount > 0) {
            $status = 'running';
        }

        return ['status' => $status, 'count' => $jobCount];
    }

    /**
     * Saves poster
     *
     * @param PosterRequest $request
     * @param PosterService $service
     *
     * @return array
     */
    public function store(PosterRequest $request, PosterService $service)
    {
        return new PosterResource($service->store($request));
    }

    /**
     * Updates poster
     *
     * @param PosterRequest $request
     * @param PosterService $service
     * @param Poster $poster
     *
     * @return array
     */
    public function update(PosterRequest $request, PosterService $service, Poster $poster)
    {
        return new PosterResource($service->update($request, $poster));
    }

    /**
     * Updates specific boolean on poster
     *
     * @param PosterRequest $request
     * @param PosterService $service
     * @param int $id poster id
     * @param string $column column to update
     *
     * @return array
     */
    public function updateSetting(PosterRequest $request, PosterService $service, $id, $column)
    {
        $service->updateSetting($id, $column, $request->boolean('value'));
        return response()->json(['success' => true]);
    }

    /**
     * Sorts posters via drag and drop
     *
     * @param PosterRequest $request
     * @param PosterService $service
     *
     * @return array
     */
    public function sort(PosterRequest $request, PosterService $service)
    {
        $service->sort($request);
        return response()->json(['success' => true]);
    }

    /**
     * Delete poster
     *
     * @param PosterService $service
     * @param int $id
     *
     * @return array
     */
    public function delete(PosterService $service, $id)
    {
        $service->delete($id);
        return response()->json(['success' => true]);
    }

    /**
     * Get now playing from Kodi service
     *
     * @param App\Services\KodiService $service
     *
     * @return array
     */
    public function kodiNowPlaying(KodiService $service)
    {
        $nowPlaying = $service->nowPlaying();
        return $nowPlaying;
    }
}
