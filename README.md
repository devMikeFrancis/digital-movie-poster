# Digital Movie Poster (DMP)

The web application creates a digital movie poster display for use on LED screens. Intended to run on a Raspberry Pi 4, but will run on any web server with Apache/NGINX, PHP 8.1+, and MySQL/Postgres.

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

## Using Laravel Sail (Docker)

1. In the project root run `cp .env.example .env`
2. In the project root folder edit the `.env` file. Set `DB_HOST` to `mariadb`
3. Then run the following commands:
    - `./vendor/bin/sail artisan key:generate`
    - `./vendor/bin/sail artisan storage:link`
    - `./vendor/bin/sail composer install`
    - `./vendor/bin/sail artisan migrate`
    - `./vendor/bin/sail npm install`
    - `./vendor/bin/sail npm run build`
    - `chgrp -R www-data storage bootstrap/cache`
    - `chmod -R ug+rwx storage bootstrap/cache`
4. In the project root folder run `./vendor/bin/sail up -d`. When you run this command for the very first time it will install the docker containers and start the site.
5. After the container first boots it may take 30-45 secs for the site to load.
6. Visit `http://localhost:8074` in your browser.
7. If you are loading up the site using a remote browser that is connected to a TV add the `rotate` param to the URL like this: `http://localhost:8074?rotate=true`
8. If you need more help on Laravel Sail, please visit `https://laravel.com/docs/sail`

---

**Recommended poster size is 1400x2000 or higher, but retain the same ratio.**

After you've added posters and are back on the DMP screen you can always return to the posters and settings configuration by clicking or tapping on the 'Coming Soon/Now Playing' header.

### IMDB/TMDB Poster Data Auto-population

When using the IMDB ID to manage poster data the application will use [TMDB API](https://developers.themoviedb.org/3/getting-started/introduction) to populate the metadata and poster image.

Enter your TMDB api key in the DMP settings.

## Updating

Visit the `About` page to check for updates.

## Now Playing API

You can send poster data to certain endpoints to tigger a `now-playing` or `stopped` event.

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

## Screenshots

Below are some screenshots taken from a local dev environment. IRL screenshots coming soon.

![Basic poster view!](https://newelementdesigns.com/assets/images/screen1.png)

![Poster view with trailer!](https://newelementdesigns.com/assets/images/screen2.png)

## License

DMP open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
