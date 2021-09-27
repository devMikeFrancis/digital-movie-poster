## Digital Movie Poster (DMP)

The web application creates a digital movie poster display for use on LED screens. Intended to run on a Raspberry Pi 4, but will run on any web server with Apache/NGINX, PHP 7.3+, and MySQL/Postgres.

## Features

-   Create/Edit movie posters
-   Show content ratings, processing logos, audience ratings
-   Random order or drag-and-drop ordering
-   Pull posters from Plex and show currently playing
-   Automatically fill in data using IMDB ID
-   Control settings such as playing speed, transition speed, etc ...
-   Control display power using HDMI-CEC control
-   Movie trailers coming soon ...

Open to new features/suggestions/requests. Please use Github issues.

Any help or contributions would be greatly appreciated. Please submit pull requests.

## Raspberry Pi 4 Image

An image of the install will be available to download in the near future.

A pre-installed image on a SD card will already be configured. Just plug-in-play and add posters.

## Self Install Requirements

1. Pi 4 or computer with at least 2GB of RAM. 4GB recommended.
2. HDMI output
3. Apache/NGINX
4. PHP 7.3+ with extensions: GD, Curl, Dom, mysql or postgres
5. Composer and Git

## Self Installation

1. Clone this repo to your web server.
2. In the root of the application run `composer install`.
3. In the root of the application duplicate the `.env.example` file and rename to `.env`. Do not delete the `.env.example` file.
4. In the root of the application run `php artisan key:generate`.
5. Run `php artisan migrate` to create the database tables.
6. Point your web root to the `public` folder of the application.
7. Increase PHP ini `max_execution_time` to at least 600.
8. Add your database credentials to the `.env` file.
9. Add your TMDB API key to the `.env` file using this existing key: `TMDB_API_V3=`

**You will need to lookup tutorials on how to boot your Pi in kiosk mode.**

NOTE: If you alter any of the application files other than the `.env` file the update process will not work as it uses `git pull` to update the application.

Make sure your web server supports/turned on mod_rewrite.

If using NGINX your configuration may need to be updated to support Laravel mod rewrites.

## License

DMP open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
