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

Open to new feature suggestions/requests. Please use Github issues.

## Self Installation

1. Clone this repo to your web server.
2. Run `composer install`.
3. Point your web root to the `public` folder of the application.
4. Increase PHP ini `max_execution_time` to at least 600.
5. Run `php artisan migrate` to create the database tables.

Make sure your web server supports mod rewrites.

NGINX configuration may need to be updated to support Laravel mod rewrites.

## License

DMP open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
