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
       │             ──── GET /api/now-playing/{service} ────────────▶    │
       │                    (proxied; credentials stay server side)       │
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

### 1. Media-server credentials in the browser — fixed

**Status: resolved.** Recorded here because the shape of the fix matters.

`GET /api/settings` returned all 64 settings columns, unauthenticated,
including `plex_token`, `jellyfin_token`, `kodi_password` and the TMDB keys.
Filtering the response alone would not have worked: the display genuinely used
those tokens client side, calling Plex and Jellyfin directly to drive "now
playing".

What changed:

- `NowPlayingController` proxies the lookups. `GET /api/now-playing/{service}`
  returns a normalised payload — `playing`, `mediaType`, `contentRating`,
  `audienceRating`, `duration`, `poster` — built server side from the stored
  credentials.
- Artwork is proxied too, via `GET /api/now-playing/{service}/poster?key=…`,
  so poster URLs no longer embed `X-Plex-Token`. The host always comes from
  settings, never the request, and the key is pattern-checked against the Plex
  library namespace / a Jellyfin item id, so it cannot be used as an open proxy.
- The browser's three direct connections are gone: the Plex websocket (which
  carried the token in its URL), the Kodi websocket, and the direct Jellyfin
  `/Sessions` poll. All three are replaced by one poll of our own API every 5s.
- `PublicSettingResource` filters the public settings payload — credentials and
  media-server hosts removed, 64 fields down to 49. `GET /api/settings/full`
  serves the admin UI the complete row, behind the same `dmp.token` gate as the
  other privileged endpoints.

The trade-off: Plex now-playing used to be event-driven and is now polled, so
it reacts within ~5s rather than instantly. Pushing these events over the Redis
/ socket.io channel the app already runs would restore that — see #5.

They are also encrypted at rest. `App\Casts\EncryptedCredential` covers the six
credential columns, so a copied `database.sqlite` yields ciphertext rather than
working tokens. The cast is deliberately more forgiving than Laravel's built-in
`encrypted`: values that are not already ciphertext pass through untouched
(which makes the encrypting migration idempotent), and anything that fails to
decrypt degrades to `null` with a log line instead of throwing — a lost
`APP_KEY` should mean "re-enter your Plex token", not a 500 on the
unauthenticated endpoint the display polls.

The cost is that `APP_KEY` is now load-bearing: rotating it makes the stored
credentials unreadable. `install.sh` only mints a key when `.env` has none.

### 2. Admin authentication — fixed

**Status: resolved.**

There had never been a working login: `routes/auth.php` referenced five
controllers that did not exist. Anyone on the network could change settings,
delete posters, and trigger `/api/update-application`, which runs `git pull`
and `composer install` on the host.

The admin UI now has a session login, and privileged endpoints accept either
that session or a Sanctum bearer token — both resolved through the `sanctum`
guard, which checks the web session first and falls back to a token. One flag,
`DMP_REQUIRE_LOGIN`, governs it, and it defaults to **on**. (It replaces the
short-lived `DMP_API_REQUIRE_TOKEN`, which had the awkward property that
enabling it locked the bundled UI out of its own write endpoints.)

First run offers to create the admin account, since an appliance has no other
way to bootstrap one; that path closes permanently as soon as a user exists,
and `DMP_ALLOW_SETUP=false` disables it in favour of `php artisan dmp:user`.

One deliberate exception, because the kiosk browser cannot log in: the
endpoints the display polls (`/api/posters`, `/api/settings`,
`/api/now-playing/*`) stay open. They return no credentials — see #1.

`/api/control-display/{command}` was a second exception until the display power
schedule moved server side; it is now behind the same gate as everything else.
See #10.

Related: `startSyncPosters()` in the display store computes its interval as
`60000 * 60 * 60 * 1000 * 4`, which is around 456 years rather than the four
hours the comment claims. It has therefore never fired. Correcting the constant
as-is would make the display poll `/api/cache-posters`, which is privileged, so
the fix is to schedule the sync alongside `dmp:display-power` rather than to
patch the number.

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

### 9. Display power schedule — fixed

**Status: resolved.**

The on/off hours used to be evaluated in the kiosk browser, which then called
`/api/control-display`. That had three consequences: the endpoint had to stay
unauthenticated even though it shells out; the schedule only applied while a
browser happened to be open on the display; and the comparison ran against a
hardcoded `America/New_York` clock, so the hours were simply wrong for anyone
outside US Eastern.

`DisplayPowerService` now owns the decision and `dmp:display-power` applies it
from the scheduler every minute, so:

- `/api/control-display/{command}` is authenticated, and is only a manual
  override. Using it clears the cached state so the next scheduled run
  re-asserts the schedule.
- The window is evaluated in the application timezone. `config/app.php` had
  `'timezone' => 'UTC'` hardcoded, ignoring the `APP_TIMEZONE` that
  `.env.example` has always set; that is wired up now.
- Windows that cross midnight (on at 20:00, off at 02:00) work, which the
  original comparison could not express.
- A command is only sent when the desired state actually changes, so the TV
  is not pestered every minute.

This needs `* * * * * cd /var/www/html && php artisan schedule:run` in cron;
`install.sh` adds it.

The optional PIR sensor feeds the same service rather than driving CEC on its
own. `hdmi-control.py` reports movement with `php artisan dmp:motion`, and
`DisplayPowerService` treats presence as a second input: the schedule decides
when the display *may* be on, the sensor decides whether it *should* be right
now. Motion cannot switch the display on outside the configured hours.

Previously the script called `cec-client` itself while the schedule ran from
the browser, so on an install using both the sensor would blank the screen and
the schedule would switch it straight back on. The script also imported
`RPi.GPIO`, which the installer never installed and which has no Pi 5 support,
so in practice it exited immediately on a fresh install. It uses `gpiozero`
now, and runs under systemd (`dmp-motion.service`) rather than an unmanaged
`@reboot` cron entry, so it restarts on failure and logs to the journal.

A sensor that is enabled but has never reported is treated as "someone is
there". A miswired sensor should cost the power saving, not the display.

### 10. Smaller things

- **The bundle is one 637 kB chunk.** The display loads the entire admin UI on
  boot. Route-level `defineAsyncComponent` would let the kiosk load only the
  dashboard.
- **Fonts load from Google Fonts over the network.** For an appliance that may
  boot without internet, self-host Inter alongside the ten fonts already
  vendored in `resources/fonts/`.
- **The socket server keeps voting state in memory.** A restart loses an
  in-progress vote; it also assumes a single instance.
- **No test covers the sync services.** They are the most complex code in the
  project and the most likely to break when a media server changes its API.
