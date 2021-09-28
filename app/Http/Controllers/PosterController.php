<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PosterRequest;
use App\Services\PosterService;
use App\Http\Resources\PostersCollection;
use App\Models\Poster;

class PosterController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $posters = $request->show_in_rotation ?
        new PostersCollection(Poster::where('show_in_rotation', true)->orderBy('ordinal')->orderBy('name')->get()) :
        new PostersCollection(Poster::orderBy('ordinal')->orderBy('name')->get());

        return response()->json(['posters' => $posters]);
    }

    public function show($id)
    {
        $poster = Poster::findOrFail($id);
        $poster->image = null;
        return response()->json(['poster' => $poster]);
    }

    public function cache(PosterService $service)
    {
        $service->cache();
        $posters = new PostersCollection(Poster::where('show_in_rotation', true)->orderBy('ordinal')->orderBy('name')->get());
        return response()->json(['posters' => $posters]);
    }

    public function store(PosterRequest $request, PosterService $service)
    {
        $poster = $service->store($request);

        return response()->json(['poster' => $poster]);
    }

    public function update(PosterRequest $request, PosterService $service, $id)
    {
        $poster = $service->update($request, $id);
        return response()->json(['poster' => $poster]);
    }

    public function updateShowInRotation(PosterRequest $request, PosterService $service, $id)
    {
        $service->updateShowInRotation($id, $request->boolean('show_in_rotation'));
        return response()->json(['success' => true]);
    }

    public function sort(PosterRequest $request, PosterService $service)
    {
        $service->sort($request);
        return response()->json(['success' => true]);
    }

    public function delete(PosterService $service, $id)
    {
        $service->delete($id);
        return response()->json(['success' => true]);
    }
}
