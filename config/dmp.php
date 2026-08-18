<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Token Protection
    |--------------------------------------------------------------------------
    |
    | DMP is designed as a LAN appliance and ships with its API open so that
    | existing installs keep working after an upgrade. Turning this on makes
    | every mutating endpoint - and the two endpoints that shell out - require
    | a Sanctum bearer token. Mint one with:
    |
    |     php artisan dmp:token "my integration"
    |
    | Note: the bundled admin UI has no login screen, so it cannot authenticate
    | once this is enabled. Enable it for installs driven over the API, or when
    | the device is reachable from anything you do not trust.
    |
    */

    'api' => [
        'require_token' => env('DMP_API_REQUIRE_TOKEN', false),
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
