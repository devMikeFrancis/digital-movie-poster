<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | When enabled (the default), the admin UI requires a login and every
    | endpoint that writes, shells out, queues work or returns credentials
    | requires either that session or a Sanctum bearer token:
    |
    |     php artisan dmp:user            create or reset the admin account
    |     php artisan dmp:token "my app"  mint a token for an integration
    |
    | The endpoints the kiosk display polls stay open either way, because the
    | display has no way to log in.
    |
    | Turning this off restores the pre-2026 behaviour where anything that can
    | reach the device can change settings and delete posters. Only do that on
    | a network you fully trust.
    |
    */

    'auth' => [
        'required' => env('DMP_REQUIRE_LOGIN', true),

        // Allow creating the first admin account through the UI when no user
        // exists yet. Set false if you would rather only ever use dmp:user.
        'allow_setup' => env('DMP_ALLOW_SETUP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | TMDB
    |--------------------------------------------------------------------------
    |
    | Where title lookups go. Overridable so the integration can be pointed at
    | a stub or a caching proxy; the API key itself lives in Settings.
    |
    */

    'tmdb' => [
        'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Motion Sensor
    |--------------------------------------------------------------------------
    |
    | Optional PIR sensor for blanking the display when nobody is around. It
    | only ever narrows the on/off hours: the schedule decides when the display
    | may be on, and the sensor decides whether it should be right now.
    |
    | If the sensor is enabled but never reports, the display is left on. A
    | miswired sensor should cost you the power saving, not the display.
    |
    */

    'motion' => [
        'enabled' => env('DMP_MOTION_SENSOR', false),
        'gpio_pin' => env('DMP_MOTION_GPIO_PIN', 21),
        'idle_minutes' => env('DMP_MOTION_IDLE_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Channel
    |--------------------------------------------------------------------------
    |
    | Where the About page checks for a newer release. This must point at the
    | same repository the installer cloned from.
    |
    */

    'update' => [
        'repository' => env('DMP_UPDATE_REPOSITORY', 'devMikeFrancis/digital-movie-poster'),
        'branch' => env('DMP_UPDATE_BRANCH', 'main'),
    ],

];
