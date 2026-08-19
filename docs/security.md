# Accounts and security

Who can change what, and how media-server credentials are kept.

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

**Settings → Account → Ask for a login to reach these screens** turns all of
this off and goes back to an open admin UI. Only do that on a network you fully
trust: anything that can reach the device can then change settings, delete
posters, read your media-server credentials and press the button that runs an
update script on the Pi. DMP is a LAN appliance either way — do not expose it to
the internet.

The display never asks for a login regardless; the setting covers the poster,
settings and voting screens.

`DMP_REQUIRE_LOGIN` in `.env` is the fallback used before the database has been
read — on a fresh install, or if the settings row is missing. It seeded the
setting when this moved into the UI, so an install that had deliberately turned
the login off kept it off. Anything unreadable falls back to requiring a login,
so a database problem cannot quietly unlock the device.

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
