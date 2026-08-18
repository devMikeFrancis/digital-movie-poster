<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The settings the kiosk display is allowed to see.
 *
 * GET /api/settings has to stay unauthenticated - the display polls it and has
 * no way to log in - so it must not carry credentials. Everything the browser
 * previously needed a token for now goes through the now-playing proxy in
 * NowPlayingController, which means media-server hosts and tokens can be
 * dropped from this payload entirely.
 *
 * This is a deny-list so that new display options appear without a code change.
 * SettingsSecrecyTest asserts that nothing credential-shaped slips through.
 */
class PublicSettingResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Never sent to the display.
     *
     * @var list<string>
     */
    public const HIDDEN = [
        // Credentials.
        'plex_token',
        'jellyfin_token',
        'kodi_username',
        'kodi_password',
        'tmdb_api_key_v3',
        'tmdb_api_key_v4',

        // Network topology: the display talks to this server, not to the
        // media servers, so it has no use for these.
        'plex_ip_address',
        'jellyfin_ip_address',
        'kodi_url',
        'kodi_port',

        // Sync configuration, only meaningful to the admin UI.
        'plex_movie_sections',
        'plex_tv_sections',
        'plex_sync_movies',
        'plex_sync_tv',
        'validate_movie_titles',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return collect(parent::toArray($request))
            ->except(self::HIDDEN)
            ->all();
    }
}
