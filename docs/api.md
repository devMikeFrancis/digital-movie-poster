# Now Playing API

Reading what a media server is playing, and pushing now-playing events from
something else.

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
