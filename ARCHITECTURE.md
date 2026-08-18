# Architecture notes

Written during the Laravel 9 → 13 refresh. The first section is what the system
does today; the rest is what I'd change, roughly in priority order. Nothing in
"Recommendations" has been implemented — each one is a behaviour change that
needs its own decision.

## How it fits together

```
                     ┌──────────────────────────────┐
  Plex / Jellyfin ──▶│ SyncPosters job (queue)      │
  / Kodi / TMDB      │   └─ PlexService              │──▶ posters table
                     │      JellyfinService          │    storage/app/public/posters
                     │      KodiService              │      <slug>.webp + _tn_<slug>.webp
                     │        (PosterProcess trait)  │
                     └──────────────────────────────┘

  Browser (kiosk)  ──── GET /api/posters, /api/settings (polled) ────▶ Laravel
       ▲                                                                  │
       │                                                          DmpEvent│
       │           socket.io :3000        Redis pub/sub                   ▼
       └────────── socketserver/server.js ◀──────────────────────── broadcast
```

Two long-lived processes sit beside Apache: a `queue:work` supervisor program
for poster syncing, and a Node socket.io server that subscribes to Redis and
relays `DmpEvent` broadcasts to connected browsers.

The Vue SPA serves two very different roles from one bundle: the **display**
(`/`, the kiosk view) and the **admin UI** (`/posters`, `/settings`, `/voting`).

---

## Recommendations

### 1. The display holds media-server credentials in the browser

`GET /api/settings` returns all 64 columns of the settings row, including
`plex_token`, `jellyfin_token`, `kodi_password` and `tmdb_api_key_v3`. That
endpoint is unauthenticated, so anything that can reach the device can read
those credentials:

```
curl http://<pi>/api/settings | jq '.plex_token, .tmdb_api_key_v3'
```

This is not simply an over-sharing bug that can be patched by filtering the
response. The display genuinely uses those tokens client-side: the store talks
to Plex and Jellyfin directly from the browser to drive "now playing"
(`store/posters.js` around lines 320, 551 and 622, plus `services/api.js`).
Redacting the fields would break now-playing outright, which is why the refresh
left this alone.

**Suggested path**, in order:

1. Add backend proxy endpoints — `GET /api/now-playing/plex`,
   `.../jellyfin` — that call the media server from PHP using the stored
   credentials. The services to do this largely exist already.
2. Repoint the store at those endpoints and delete `apiCallPlex`.
3. Split the settings response: a public projection for the display (display
   and theme options only) and a full one behind `dmp.token` for the admin UI.
4. Move the secrets out of the settings table into `.env`, or encrypt them with
   `encrypted` casts so they are not readable straight out of the SQLite file.

Step 1 alone removes the exposure; the rest is cleanup.

### 2. There is no login

Deleting the broken Breeze scaffolding removed five controllers that did not
exist, but it did not change the security posture: there has never been a
working login. Anyone on the network can change settings, delete posters, and
trigger `/api/update-application`, which runs `git pull` and `composer install`
on the host.

`DMP_API_REQUIRE_TOKEN` (added in this refresh) closes that for API clients,
but the bundled admin UI cannot authenticate, so turning it on locks the UI out
of its own write endpoints. The real fix is session auth on the admin routes —
a single user, seeded at install time — with Sanctum tokens kept for machine
integrations. Sanctum already supports both against the same `User` model.

### 3. `settings` is a 64-column single-row table

Every new option means a migration, a column, a validation rule in the
120-line `SettingsRequest`, and a field in the 1,469-line `Settings.vue`. The
23 migrations named `add_*_to_settings_table` are the evidence.

The controller also writes with `Setting::where('id', 1)->update(...)`, which
silently does nothing if the seeded row is ever missing, and the request
requires **every** field on every save, so a partial update is impossible.

Consider a typed settings object persisted to a single JSON column (with an
`AsArrayObject` or custom cast), or a key/value table with a small
`Settings::get()/set()` facade. Either way `Setting::firstOrCreate()` should
replace the hardcoded id.

### 4. The Pinia store is a god object holding DOM state

`store/posters.js` is 782 lines and mixes three unrelated jobs: server state
(posters, settings), playback state (which poster is showing, ratings,
processing logos), and browser resources — it owns 13 timers, an `<audio>`
element and an iframe handle, and writes them onto `window`:

```js
window.audio = new Audio('/storage/music/' + this.theme_music);
window.transitionImagesInterval = setInterval(...);
```

`router/index.js` then reaches back into those globals to clean up on
navigation. Suggested split:

- keep the store for data fetched from the API;
- move the slideshow loop, theme music and trailer playback into a
  `usePosterPlayer()` composable that owns its own timers and tears them down
  in `onScopeDispose`, so the router guard is unnecessary.

### 5. Everything polls, despite a socket already being connected

The display polls settings every 10s and posters every 20s, and separately
polls Jellyfin on a timer. Redis and socket.io are already wired up and already
push `DmpEvent`. Broadcasting a `SettingsUpdated` / `PostersChanged` event from
the backend would let the display drop most of its polling and react instantly.

### 6. `PosterProcess` is a trait doing three jobs

It is mixed into `PosterService` and all three media services, and combines
image resizing, a TMDB HTTP client, and poster persistence, sharing a mutable
public `$settings` property with whatever class uses it. Traits used this way
are inheritance in disguise — the dependency is invisible at the call site.

Split it into constructor-injected services: `PosterImageWriter` (decode,
scale, encode, write), `TmdbClient` (metadata lookup), and leave persistence on
the model or `PosterService`.

### 7. Sync services are resolved by hand

`PlexService`, `JellyfinService` and `KodiService` all implement
`MovieSyncInterface`, but `PosterService::cache()` still does:

```php
if ($this->settings->plex_service) { (new PlexService())->syncMedia(); }
if ($this->settings->jellyfin_service) { ... }
```

Tag the three bindings in a service provider and iterate them, letting each
service report whether it is enabled. Adding a fourth source then touches one
class instead of three branches. It also makes them mockable — right now
`cache()` cannot be tested without real network calls.

### 8. `Settings.vue` is 1,469 lines

It already renders three tabs. Splitting it per tab, with a shared composable
for load/save/dirty-tracking, would make it navigable. `Voting.vue` (726) and
`PostersEdit.vue` (571) are the next candidates.

### 9. Smaller things

- **`api.js` is a class with one method and no state.** It is imported for a
  single Plex call. Once that call moves server-side (#1), delete the file.
- **The bundle is one 637 kB chunk.** The display loads the entire admin UI on
  boot. Route-level `defineAsyncComponent` would let the kiosk load only the
  dashboard.
- **Fonts load from Google Fonts over the network.** For an appliance that may
  boot without internet, self-host Inter alongside the ten fonts already
  vendored in `resources/fonts/`.
- **`hdmi-control.py` runs from `@reboot` cron** and is unmanaged. A small
  systemd unit would give it restart-on-failure and logs.
- **The socket server keeps voting state in memory.** A restart loses an
  in-progress vote; it also assumes a single instance.
- **No test covers the sync services.** They are the most complex code in the
  project and the most likely to break when a media server changes its API.
