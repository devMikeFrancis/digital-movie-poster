# Digital Movie Poster (DMP)

The web application creates a digital movie poster display for use on LED screens. Intended to run on a Raspberry Pi 4, but will run on any web server with Apache/NGINX and PHP 8.3+.

Built on Laravel 13, Vue 3 and Tailwind 4. Data lives in a SQLite file by
default, so there is no database server to install or maintain.

## Features

-   Create/Edit movie posters
-   Sync posters from Plex, Kodi and Jellyfin and show currently playing
-   Show content ratings, processing logos, audience ratings
-   Random order or drag-and-drop ordering
-   Fade, cross-fade, vertical slide or cut between posters
-   Fill the screen with the poster, hide the header text, show your theater name
-   Automatically fill in data using IMDB ID
-   Control settings such as playing speed, transition speed, etc ...
-   Control display power using HDMI-CEC on a schedule
-   Show Runtime
-   Movie trailers
-   Movie theme music
-   Movie voting — guests vote from their phones by scanning a QR code on the display

Open to new features/suggestions/requests. Please use Github issues.

Any help or contributions would be greatly appreciated. Please submit pull requests.

## Installing

On a Raspberry Pi running Raspberry Pi OS Bookworm or newer:

```bash
wget -O install.sh https://raw.githubusercontent.com/devMikeFrancis/digital-movie-poster/main/install.sh
chmod u+x install.sh
sudo ./install.sh $USER
```

The install takes several minutes and reboots into the display. Settings are at
`http://raspberrypi.local/posters`, or the Pi's IP address.

Full walkthrough, including preparing the SD card:
**[Installation](docs/installation.md)**.

## Documentation

| | |
| :--- | :--- |
| [Installation](docs/installation.md) | Requirements, SD card, running the installer |
| [Updating](docs/updating.md) | Taking a release, upgrading a pre-Laravel-13 install, MariaDB to SQLite |
| [Adding and editing posters](docs/posters.md) | Artwork, and filling in details from TMDB |
| [The display](docs/display.md) | Transitions, fill screen, theater name, rating limits |
| [Movie voting](docs/voting.md) | Putting posters to a vote from guests' phones |
| [Display power and schedule](docs/power-schedule.md) | HDMI-CEC schedule and the motion sensor |
| [Accounts and security](docs/security.md) | The admin login, API tokens, credential encryption |
| [Now Playing API](docs/api.md) | Reading what is playing, pushing now-playing events |
| [Development](docs/development.md) | Running DMP locally, and the test suites |
| [Architecture](ARCHITECTURE.md) | How the pieces fit together |

There is no GitHub wiki — [`docs/`](docs/) is the documentation, and it lives
with the code so a change and its explanation arrive together.

## Credits

Originally created by Don Jones. Maintained by
[Mike Francis](https://github.com/devMikeFrancis).

## License

DMP is open-sourced software licensed under the [MIT license](LICENSE).
