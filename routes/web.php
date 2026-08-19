<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Every path the Vue router knows about has to be served the app shell, or a
| reload on that path answers 404 instead of reaching the front end. These were
| ten copies of the same closure, which meant adding a screen and forgetting
| this file left the new page working until the first hard refresh.
|
| Deliberately a list rather than a catch-all: an unknown path should still be
| a 404 rather than an app shell that renders nothing.
|
*/

$spaRoutes = [
    '/',
    '/dashboard',
    '/posters',
    '/posters/{any}',
    '/display',
    '/settings',
    '/about',
    '/voting',
    '/vote',
    '/login',
];

foreach ($spaRoutes as $path) {
    Route::get($path, fn () => view('app'))->where('any', '.*');
}
