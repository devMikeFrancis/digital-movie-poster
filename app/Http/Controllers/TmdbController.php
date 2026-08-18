<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Title lookup for the poster editor.
 *
 * Privileged: these spend the operator's TMDB API key, and are only ever
 * called from the admin UI.
 */
class TmdbController extends Controller
{
    /**
     * Titles matching a name, so a poster can be created without knowing the
     * IMDB id up front.
     */
    public function search(Request $request, TmdbService $tmdb): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'media_type' => ['nullable', 'in:movie,tv,show'],
        ]);

        return $this->attempt(fn () => [
            'results' => $tmdb->search($data['query'], $data['media_type'] ?? 'movie'),
        ]);
    }

    /**
     * Everything needed to fill in the form, by either id.
     *
     * The editor calls this when a search result is picked, and when "Fetch
     * media" is used with an IMDB id typed in directly.
     */
    public function title(Request $request, TmdbService $tmdb): JsonResponse
    {
        $data = $request->validate([
            'imdb_id' => ['required_without:tmdb_id', 'nullable', 'string', 'max:20'],
            'tmdb_id' => ['required_without:imdb_id', 'nullable', 'integer'],
            'media_type' => ['nullable', 'in:movie,tv,show'],
        ]);

        $mediaType = $data['media_type'] ?? 'movie';

        return $this->attempt(fn () => ['title' => empty($data['tmdb_id'])
            ? $tmdb->detailsByImdbId($data['imdb_id'], $mediaType)
            : $tmdb->detailsByTmdbId($data['tmdb_id'], $mediaType)]);
    }

    /**
     * A missing key, a rejected key or an unreachable TMDB are all things the
     * operator can act on, so they come back as a readable message rather than
     * a stack trace.
     *
     * @param  callable(): array<string, mixed>  $work
     */
    private function attempt(callable $work): JsonResponse
    {
        try {
            return response()->json($work());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::warning('TMDB request failed: '.$e->getMessage());

            return response()->json(['message' => 'Could not reach TMDB. Try again in a moment.'], 502);
        }
    }
}
