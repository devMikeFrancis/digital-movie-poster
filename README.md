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
-   Control display power using HDMI-CEC control
-   Show Runtime
-   Movie trailers
-   Movie theme music
-   Movie Voting! See Wiki for more info

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

When using the IMDB ID to manage poster data the application will use [TMDB API](https://developers.themoviedb.org/3/getting-started/introduction) to populate the metadata and poster image.

Enter your TMDB api key in the DMP settings.

## Updating

Visit the `About` page to check for updates, or run `./update.sh` on the device.

Unlike previous versions, `update.sh` refuses to run when the working tree has
local changes rather than discarding them with `git reset --hard`. Commit or
stash your edits first.

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

DMP is built as a LAN appliance and **has no login screen**. Anything that can
reach the device can change settings and delete posters. Do not expose it to
the internet.

For installs you drive over the API, you can require a token on every write and
on the two endpoints that shell out on the host:

```bash
php artisan dmp:token "my integration"   # prints the token once
```

Then set `DMP_API_REQUIRE_TOKEN=true` in `.env` and send the token as
`Authorization: Bearer <token>`. Read-only endpoints stay open so the kiosk
display keeps working.

Note that the bundled admin UI cannot send a token, so enabling this locks the
UI out of its own write endpoints. See
[ARCHITECTURE.md](ARCHITECTURE.md#2-there-is-no-login) for the fuller picture.

## Now Playing API

You can send poster data to certain endpoints to trigger a `now-playing` or `stopped` event.

These endpoints require a bearer token when `DMP_API_REQUIRE_TOKEN=true`.

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

## License

DMP open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
