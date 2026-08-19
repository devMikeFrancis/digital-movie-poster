# DMP documentation

Everything beyond the overview in the [project README](../README.md).

![The display](images/display.png)

## Getting it running

- **[Installation](installation.md)** — what you need, preparing the SD card,
  and running the installer on a Raspberry Pi.
- **[Updating](updating.md)** — updating from the About page, upgrading an
  install that predates Laravel 13, and moving an old MariaDB database to
  SQLite.

## Using it

- **[Adding and editing posters](posters.md)** — artwork, and filling in
  titles, ratings and trailers automatically from TMDB.
- **[The display](display.md)** — what goes on screen and how it changes:
  transitions, filling the screen with the poster, the header text, the theatre
  name, and the rating limits.
- **[Movie voting](voting.md)** — putting posters to a vote and letting guests
  choose from their phones.
- **[Display power and schedule](power-schedule.md)** — turning the TV on and
  off by HDMI-CEC on a schedule, and the optional motion sensor.

## Running it safely

- **[Accounts and security](security.md)** — the admin login, API tokens, and
  how media-server credentials are protected.
- **[Now Playing API](api.md)** — reading what is playing, and pushing
  now-playing events from something else.

## Working on it

- **[Development](development.md)** — running DMP locally with or without
  Docker, and the two test suites.
- **[Architecture](../ARCHITECTURE.md)** — how the pieces fit together and
  where the rough edges are.

---

There is no GitHub wiki. This directory is the documentation, and it lives with
the code so a change and its explanation arrive together.

DMP was created by Don Jones, who built version 1. Version 2 onwards — the
refreshed architecture and continuing development — is by
[Mike Francis](https://github.com/devMikeFrancis).
