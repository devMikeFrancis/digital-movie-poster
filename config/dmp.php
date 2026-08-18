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
