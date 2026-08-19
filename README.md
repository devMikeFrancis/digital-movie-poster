# Digital Movie Poster (DMP)

The web application creates a digital movie poster display for use on LED screens. Intended to run on a Raspberry Pi 4, but will run on any web server with Apache/NGINX and PHP 8.3+.

Built on Laravel 13, Vue 3 and Tailwind 4. Data lives in a SQLite file by
default, so there is no database server to install or maintain. See
[ARCHITECTURE.md](ARCHITECTURE.md) for how the pieces fit together and where
the rough edges are.

## Features

-   Create/Edit movie posters
-   Sync posters from Plex, Kodi and Jellyfin and show currently playing
-   Show content ratings, processing logos, audience ratings
-   Random order or drag-and-drop ordering
-   Fade in/out or vertical slide transitions
-   Automatically fill in data using IMDB ID
-   Control settings such as playing speed, transition speed, etc ...
-   Control display power using HDMI-CEC on a schedule
-   Show Runtime
-   Movie trailers
-   Movie theme music
-   Movie voting — guests vote from their phones by scanning a QR code on the display

Open to new features/suggestions/requests. Please use Github issues.

Any help or contributions would be greatly appreciated. Please submit pull requests.

## Self Install Requirements

1. Pi 4 with at least 2GB of RAM. 4GB recommended.
2. 16G or higher SD card
3. Raspberry Pi OS Bookworm (Debian 12) or newer

## Self Installation

### Prepare the SD Card

1. Download and open the Pi Imager [here](https://www.raspberrypi.com/software/)
2. For the Operating System choose `Raspberry Pi OS Other` -> `Raspberry Pi OS Lite (64-bit)`
3. Click the settings cog and check `Enable SSH` and choose `Use password authentication`
4. Make sure `Set username and password` is checked. Use the default login or enter your own. `raspberry` is the default password.
5. If you are not using the onboard ethernet port, check `Configure wireless LAN` and enter your wifi information.
6. Save your settings.
7. Choose your `Storage` device then click the `Write` button. This will take several minutes.
8. Once your SD card is ready insert it into your Raspberry Pi and turn it on.
9. When the Pi is finished booting we need to access the console on the device.

### Access Raspberry Pi Console to Install DMP

1. Accessing Pi console option 1 -
    - Connect the Pi to a display and connect a keyboard.
    - Type in your password from step 4 above. `raspberry` is the default password.
    - Once your are in the console `go to step 3`.
2. Accessing Pi console option 2 -
    - Using a Mac or Windows open your terminal.
    - Type `ssh usernameFromStep4@raspberrypi.local` or use the IP address instead of raspberrypi.local.
    - Next enter the password from step 4
    - If the terminal asks to accept the ssh connection type Y or yes.
    - Once you are in the console `go to step 3`.
3. In the Pi console enter the following commands:
    - `wget -O install.sh https://raw.githubusercontent.com/devMikeFrancis/digital-movie-poster/main/install.sh`
    - `chmod u+x install.sh`
    - `sudo ./install.sh $USER`

The install will take several minutes. Once it is finished the Raspberry Pi will reboot. If all goes well it will boot into the DMP interface.

You can access the settings via any web browser.

`http://raspberrypi.local/posters` or `http://the ip address of the Pi/posters`

## Local development with Docker

1. `cp .env.example .env`
2. `docker compose build`
3. `docker compose up -d`
4. Then, inside the app container:
    - `docker compose exec app composer install`
    - `docker compose exec app php artisan key:generate`
    - `docker compose exec app php artisan migrate`
    - `docker compose exec app php artisan storage:link`
    - `docker compose exec app npm install && docker compose exec app npm run build`
5. Visit `http://localhost:8074`.
6. If you are loading the site in a browser attached to a TV, add the `rotate`
   param: `http://localhost:8074?rotate=true`

## Local development without Docker

You need PHP 8.3+ (with `gd` or `imagick`, `sqlite3`, `intl`, `zip`, `mbstring`),
Node 22+, and Redis if you want the socket features.

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build
php artisan serve          # http://127.0.0.1:8000
node socketserver/server.js # separate terminal, needs Redis
```

Run the test suite with `php artisan test`, and check formatting with
`./vendor/bin/pint`.

---

**Recommended poster size is 1400x2000 or higher, but retain the same ratio.**

After you've added posters and are back on the DMP screen you can always return to the posters and settings configuration by clicking or tapping on the 'Coming Soon/Now Playing' header.

### IMDB/TMDB Poster Data Auto-population

Poster metadata and artwork come from the
[TMDB API](https://developer.themoviedb.org/docs). Enter your TMDB API key in
Settings first - everything below needs it.

DMP identifies titles by their IMDB ID. IMDB itself has no public API, so TMDB
is what answers; it accepts IMDB IDs as well as its own, and hands the IMDB ID
back when you search.

**Find a title** - if you do not know the IMDB ID, search by name in the poster
editor. Results show the artwork, year and whether it is a movie or a TV show,
so remakes and same-named titles are easy to tell apart. Pick one and confirm,
and the IMDB ID, title, rating, audience score, runtime and trailer are filled
in for you.

**Fetch media** - if you already have the IMDB ID, this pulls the same data in
straight away and shows the artwork, so you can check it before saving. Without
it the artwork is still downloaded, but not until you save.

The artwork is downloaded, resized and converted to WebP on save. If that
fails, the poster is not created and the reason is shown - rather than a poster
appearing with no artwork.

`IMAGE_DRIVER` chooses how images are processed. It prefers `imagick`, which
`install.sh` puts on the Pi, and falls back to `gd` when the imagick extension
is not installed - so a machine without it still works.

## Movie voting

Put a few posters up for a vote and let people in the room choose from their
phones. There is no wiki — this is the documentation.

**Requirements.** Voting rides on the Node socket server in `socketserver/`.
`install.sh` sets it up; locally, run `node socketserver/server.js` alongside
`php artisan serve`. The socket server also talks to Redis for now-playing, but
it keeps running and retrying without it, so voting works on a machine that has
no Redis.

1. Sign in and go to **Voting**. The **Setup** tab is where you build a
   session — no name needed, it is the admin screen.
2. Choose posters by hand, or tick **Pick them at random** and set how many go
   into the running.
3. Set **Picks per voter** — how many posters each person may choose. Note that
   allowing as many picks as there are posters lets everyone vote for
   everything, which usually ends in a tie.
4. Press **Open for joining**. A QR code appears in the corner of the display.
5. People scan it, land on `/vote`, and enter a name. That page is public on
   purpose: voters are guests and have no account. It cannot change any
   setting — it can only cast votes into a session you opened.
6. Press **Start voting** on the **Live session** tab when everyone is in. That
   tab is the console for the running vote: who has joined, who has voted, and
   the count against each poster as it lands. Results appear when the timer
   expires. **Join the vote** opens the voter page in a new tab so whoever is
   running the session can take part too — keep the console tab open, since it
   holds the controls.
   Latecomers are welcome: anyone who scans and joins after voting has started
   picks up the clock where it is and votes with whatever time is left.
7. **Close voting** ends the round early and shows the result there and then,
   rather than waiting out the clock.
8. Ten seconds after the results, the session closes itself and the QR code
   disappears. **Close session** ends it sooner. A session that is opened for
   joining and never started closes on its own after ten minutes, so the QR code
   is not left stranded on the display. The winner is not lost at that
   point: it stays on the Setup tab, and on the voting page for anyone still
   watching, labelled as the last session's winner until the next round starts.

The number of picks is enforced on the server as well as in the page, so a
modified client cannot vote more times than the session allows.

Votes survive the connection. Phones lock, tabs get closed, and a backgrounded
tab has its websocket closed out from under it - a vote already cast stays in
the count for the rest of the round. Each browser keeps a voter id in local
storage, so coming back rejoins the same ballot: the page shows the picks
already made, changing them replaces that vote, and reconnecting does not cast a
second one.

## Display on/off schedule

With `Use HDMI CEC Controls` enabled, DMP turns the TV on and off at the hours
set in Settings. This runs on the device from Laravel's scheduler, so it works
whether or not a browser is open, and it needs a cron entry — `install.sh` adds
one:

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

**Set `APP_TIMEZONE` in `.env` to your own timezone**, or the hours are applied
in UTC. Windows that cross midnight (on at 20:00, off at 02:00) are fine.

To drive the display by hand:

```bash
php artisan dmp:display-power standby
```

### Motion sensor (optional)

With a PIR sensor wired to a GPIO pin, DMP can blank the display when the room
is empty. The sensor only ever narrows the hours above — it will not switch the
display on outside them.

In `.env`:

```bash
DMP_MOTION_SENSOR=true
DMP_MOTION_GPIO_PIN=21
DMP_MOTION_IDLE_MINUTES=5
```

Then start the service:

```bash
sudo systemctl enable --now dmp-motion.service
```

Check what it is doing with `php artisan dmp:motion --status`, or
`journalctl -u dmp-motion -f` for the sensor's own log. If the sensor is
enabled but never reports, the display stays on — a miswired sensor costs you
the power saving, not the display.

## Updating

Visit the `About` page to check for updates, or run `./update.sh` on the device.

Unlike previous versions, `update.sh` refuses to run when the working tree has
local changes rather than discarding them with `git reset --hard`. Commit or
stash your edits first.

### Upgrading from a pre-Laravel-13 install

Installs made before this release run PHP 8.1, and this release needs 8.3 or
newer, so `update.sh` cannot upgrade them in place — it checks the PHP version
first and stops before changing anything.

Re-run the installer instead. It is safe to run again, and it upgrades PHP,
installs what is missing, and leaves your database and posters alone:

```bash
wget -O install.sh https://raw.githubusercontent.com/devMikeFrancis/digital-movie-poster/main/install.sh
chmod u+x install.sh
sudo ./install.sh $USER
```

Afterwards the settings screen will ask you to create an administrator account,
and your media-server credentials get encrypted in place on the first
`php artisan migrate`.

### Migrating an existing MariaDB install to SQLite

New installs use SQLite. Existing MariaDB installs keep working — uncomment the
`DB_CONNECTION=mysql` block in your `.env`. To move across:

Leave `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`
while you do this — the copy step still needs them to read the old database.
Back up `database/` and take a `mysqldump` first.

```bash
php artisan down
# 1. Switch the default connection to SQLite, keeping the mysql credentials
sed -i 's/^DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
touch database/database.sqlite
php artisan migrate --force
# 2. Copy the two tables that hold your data
php artisan tinker --execute='
    $old = DB::connection("mysql");
    foreach (["settings", "posters"] as $table) {
        DB::table($table)->delete();
        foreach ($old->table($table)->get() as $row) {
            DB::table($table)->insert((array) $row);
        }
    }
'
php artisan up
```

Poster images live on disk under `storage/app/public/posters` and are not
affected.

## Security

The admin UI requires a login - a username and a password. The first time you
open it on a new device it offers to create the administrator account; after
that the same screen asks you to sign in. You can also manage the account from
the console:

```bash
php artisan dmp:user                     # prompts
php artisan dmp:user --username=mike     # prompts for the password only
```

You can also change your username and password from **Settings → Account**,
which needs your current password. `dmp:user` is the way back in if you forget
it, since there is no email address on the account and no password reset: the
device sends no mail, so an address would only be a login name that had to look
like one.

Privileged endpoints — anything that writes, shells out, or returns credentials
— accept either that session or a Sanctum bearer token, so integrations keep
working:

```bash
php artisan dmp:token "my integration"   # prints the token once
```

The endpoints the kiosk display polls stay open, because the display has no way
to sign in. They return no credentials.

Set `DMP_REQUIRE_LOGIN=false` to turn all of this off and go back to an open
admin UI. Only do that on a network you fully trust. DMP is still a LAN
appliance: do not expose it to the internet.

Media-server credentials (Plex and Jellyfin tokens, the Kodi login, and both
TMDB keys) are **encrypted at rest** using `APP_KEY`, so a copied
`database.sqlite` is not also a working set of tokens.

> **Do not regenerate `APP_KEY` on a running install.** It is the decryption
> key for those credentials. `php artisan key:generate` on an existing install
> makes them unreadable, and you will have to re-enter them in Settings. The
> installer only mints a key when `.env` does not already have one. To rotate
> deliberately: clear the credential fields in Settings, run `key:generate`,
> then re-enter them.

Media-server credentials stay on the server. The display asks DMP what is
playing (`/api/now-playing/{service}`) instead of calling Plex or Jellyfin
itself, and artwork is proxied through
`/api/now-playing/{service}/poster`, so no token is ever sent to a browser.
`GET /api/settings` is filtered to display options only; the admin UI reads the
full row, credentials included, from `GET /api/settings/full`, which is gated
by the token below.

## Now Playing API

You can send poster data to certain endpoints to trigger a `now-playing` or `stopped` event.

These endpoints require a bearer token (or an admin session) unless
`DMP_REQUIRE_LOGIN=false`.

### Reading now playing

`GET /api/now-playing/{service}` — where `service` is `plex`, `jellyfin` or
`kodi` — reports what the configured media server is playing. This is what the
display polls; it needs no credentials because DMP holds them.

```javascript
{
    "playing": true,
    "mediaType": "movie",
    "title": "Blade Runner",
    "contentRating": "R",
    "audienceRating": 8.6,
    "duration": 112,                                  // minutes
    "poster": "http://.../api/now-playing/plex/poster?key=..."
}
```

`playing` is `false` when nothing is playing, the service is switched off
(`enabled: false`), or the media server is unreachable (`reachable: false`).

Plex now-playing is polled every 5 seconds rather than pushed. Earlier versions
opened a websocket to Plex from the browser, which required putting
`X-Plex-Token` in the URL.

| Method | Endpoint           | Data                   | Description                               |
| :----- | :----------------- | :--------------------- | ----------------------------------------- |
| `POST` | `/api/now-playing` | See data payload below | This will put DMP into `now-playing` mode |
| `POST` | `/api/stopped`     | N/A                    | This will end the `now-playing` mode      |

### now-playing Data Payload

```javascript
{
    "mediaType": string, // **Required** movie or tv
    "poster": string, // **Required** URL to poster image. https://www...jpg
    "contentRating": string, // **Optional** MPAA/TV Rating - G, PG, PG-13...etc, TV-Y, TV-7, TV-MA...etc
    "audienceRating": integer, // **Optional** Number 1-10. Decimal allowed i.e. 8.5
    "duration": integer // **Optional** Number in minutes i.e. 112
}
```

## Credits

Originally created by Don Jones. Maintained by
[Mike Francis](https://github.com/devMikeFrancis).

## License

DMP is open-sourced software licensed under the [MIT license](LICENSE).
