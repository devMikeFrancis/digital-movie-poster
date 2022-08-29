## Digital Movie Poster (DMP)

The web application creates a digital movie poster display for use on LED screens. Intended to run on a Raspberry Pi 4, but will run on any web server with Apache/NGINX, PHP 8.1+, and MySQL/Postgres.

## Features

-   Create/Edit movie posters
-   Show content ratings, processing logos, audience ratings
-   Random order or drag-and-drop ordering
-   Fade in/out or vertical slide transitions
-   Pull posters from Plex and Jellyfin and show currently playing
-   Automatically fill in data using IMDB ID
-   Control settings such as playing speed, transition speed, etc ...
-   Control display power using HDMI-CEC control
-   Show Runtime
-   Movie trailers
-   Movie theme music
-   Movie Voting! See WIKI for more info

Open to new features/suggestions/requests. Please use Github issues.

Any help or contributions would be greatly appreciated. Please submit pull requests.

## Self Install Requirements

1. Pi 4 or computer with at least 2GB of RAM. 4GB recommended.
2. Apache/NGINX w/mod_rewrite enabled
3. PHP 8.1+ with extensions: GD, Curl, Dom, MySql/Postgres
4. Composer and Git

## Self Installation

See the WIKI

### Movie Voting Service

The movie voting service runs on a socket.io server locally.

If you want to use the movie voting feature you will need to go into the `socketserver` folder and run:
`npm install`

Next you will need to install PM2:

`npm install -g pm2`

Start the voting socket.io server run:

`pm2 start server.js`

Now the voting service will listen for connections when navigating to the `/voting` path in your browser.

**Recommended poster size is 1400x2000 or higher, but retain the same ratio.**

After you've added posters and are back on the DMP screen you can always return to the posters and settings configuration by clicking or tapping on the 'Coming Soon/Now Playing' header.

### Poster Data Auto-population

When using the IMDB ID to manage poster data the application will use [TMDB API](https://developers.themoviedb.org/3/getting-started/introduction) to populate the metadata and poster image.

### Plex

To use Plex, go to the settings configuration, check the checkbox to use the Plex service and enter your Plex IP address and Plex auth token. You can find your Plex auth token [here](https://support.plex.tv/articles/204059436-finding-an-authentication-token-x-plex-token/).

Anytime a movie is played on Plex it will show the Now Playing poster.

### Sorting Posters

On the poster list screen you can drag-and-drop each poster by the thumbnail to sort. If you have chosen to use a random order in the settings this list order is ignored.

### A Note on Deleting Posters

If you delete a poster that was cached from a service like Plex the poster will return on next cache. `The cache service runs every 4 hours when the DMP screen is active`. You can always choose which posters you want in the rotation.

## Updating

In the settings view there is a `Update DMP` link next to the `Save Settings` button. Clicking Update DMP will update the application.

After the application updates you will need to refresh the browser, or if using the Pi in kiosk mode, restart the Pi.

## Screenshots

Below are some screenshots taken from a local dev environment. IRL screenshots coming soon.

![Basic poster view!](https://newelementdesigns.com/assets/images/screen1.png)

![Poster view with trailer!](https://newelementdesigns.com/assets/images/screen2.png)

## License

DMP open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
