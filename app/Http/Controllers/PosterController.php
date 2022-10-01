<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PosterRequest;
use App\Services\PosterService;
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

    public function showInRotation(Request $request)
    {
        $allShowInRotation = $request->boolean('all_show_in_rotation');
        Poster::query()->update(['show_in_rotation' => $allShowInRotation]);

        return ['success' => true];
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
}
