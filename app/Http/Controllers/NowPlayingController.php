<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\JellyfinService;
use App\Services\KodiService;
use App\Services\PlexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Proxies "now playing" lookups to the configured media server.
 *
 * The display used to call Plex and Jellyfin straight from the browser, which
 * meant shipping plex_token and jellyfin_token to every client that loaded the
 * page. These endpoints do the same work server side using the stored
 * credentials, so the browser only ever sees the normalised result.
 */
class NowPlayingController extends Controller
{
    private const SERVICES = ['plex', 'jellyfin', 'kodi'];

    public function show(string $service): JsonResponse
    {
        if (! in_array($service, self::SERVICES, true)) {
            return response()->json(['message' => 'Unknown service: '.$service], 404);
        }

        $settings = Setting::first();

        if (! $settings || ! $settings->{$service.'_service'}) {
            return response()->json(['playing' => false, 'enabled' => false]);
        }

        try {
            $payload = match ($service) {
                'plex' => app(PlexService::class)->nowPlaying(),
                'jellyfin' => app(JellyfinService::class)->nowPlaying(),
                'kodi' => $this->kodiNowPlaying(),
            };
        } catch (Throwable $e) {
            // A media server that is off or unreachable is normal, not an error
            // worth surfacing to the display: it just means nothing is playing.
            Log::debug('now-playing lookup failed for '.$service.': '.$e->getMessage());

            return response()->json(['playing' => false, 'reachable' => false]);
        }

        $payload['enabled'] = true;
        $payload['reachable'] = true;

        // Hand back a URL on this server rather than one carrying a token.
        if (! empty($payload['posterKey'])) {
            $payload['poster'] = route('now-playing.poster', [
                'service' => $service,
                'key' => $payload['posterKey'],
            ]);
        }

        unset($payload['posterKey']);

        return response()->json($payload);
    }

    /**
     * Stream artwork from the media server using the stored credentials.
     */
    public function poster(Request $request, string $service): Response
    {
        $key = (string) $request->query('key', '');

        if (! in_array($service, ['plex', 'jellyfin'], true)) {
            abort(404, 'Unknown service: '.$service);
        }

        $settings = Setting::first();

        if (! $settings || ! $settings->{$service.'_service'}) {
            abort(404, 'Service is not enabled.');
        }

        try {
            $response = $service === 'plex'
                ? app(PlexService::class)->fetchPoster($key)
                : app(JellyfinService::class)->fetchPoster($key);
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        } catch (Throwable $e) {
            Log::debug('artwork proxy failed for '.$service.': '.$e->getMessage());
            abort(502, 'Could not reach the media server.');
        }

        if (! $response->successful()) {
            abort(404, 'Artwork not found.');
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'image/jpeg',
            // The key changes when the item changes, so this is safe to cache.
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function kodiNowPlaying(): array
    {
        $json = app(KodiService::class)->nowPlaying();
        $item = $json[0]['result']['item'] ?? null;

        if (! $item) {
            return ['playing' => false];
        }

        $art = $json[1]['result']['item']['art']['poster'] ?? null;

        return [
            'playing' => true,
            'mediaType' => 'movie',
            'title' => $item['title'] ?? null,
            'contentRating' => $item['mpaa'] ?? null,
            'audienceRating' => $item['rating'] ?? null,
            'duration' => isset($item['runtime']) && is_numeric($item['runtime'])
                ? (int) round($item['runtime'] / 60)
                : null,
            // Kodi hands back a fully-qualified image:// URL with no credentials.
            'poster' => $art ? rtrim(urldecode(str_replace('image://', '', $art)), '/') : null,
        ];
    }
}
