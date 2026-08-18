<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosterRequest;
use App\Http\Resources\PosterResource;
use App\Http\Resources\PostersCollection;
use App\Models\Poster;
use App\Services\PlexService;
use App\Services\PosterService;
use Illuminate\Http\Request;

// use App\Models\Setting;

class PosterController extends Controller
{
    public function __construct() {}

    /**
     * Get posters
     *
     *
     * @return array
     */
    public function index(Request $request)
    {
        $posters = $request->show_in_rotation ?
        Poster::where('show_in_rotation', true) :
        Poster::orderBy('ordinal');

        // $settings = Setting::first();

        return new PostersCollection(
            $posters->orderBy('ordinal')->orderBy('name')->get()
        );
    }

    /**
     * Get poster
     *
     *
     * @return array
     */
    public function show(Poster $poster)
    {
        return new PosterResource($poster);
    }

    /**
     * Saves poster
     *
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
     * @param  int  $id  poster id
     * @param  string  $column  column to update
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
     * @param  PosterRequest  $request
     * @return array
     */
    public function sort(Request $request, PosterService $service)
    {
        $service->sort($request);

        return response()->json(['success' => true]);
    }

    public function showInRotation(Request $request)
    {
        $allShowInRotation = $request->boolean('all_show_in_rotation');
        Poster::query()->update(['show_in_rotation' => $allShowInRotation]);

        return ['success' => true];
    }

    /**
     * Delete poster
     *
     * @param  int  $id
     * @return array
     */
    public function delete(PosterService $service, $id)
    {
        $service->delete($id);

        return response()->json(['success' => true]);
    }

    public function getServiceSections(PlexService $plexService, $service)
    {
        if ($service !== 'plex') {
            return response()->json(['message' => 'Unknown service: '.$service], 404);
        }

        return $plexService->getSections();
    }
}
