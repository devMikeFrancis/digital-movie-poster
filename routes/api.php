<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NowPlayingController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Read-only endpoints
|--------------------------------------------------------------------------
|
| The kiosk display polls these on a timer and has no way to authenticate,
| so they stay open regardless of DMP_API_REQUIRE_TOKEN. Nothing here may
| return a credential: GET /api/settings is filtered by PublicSettingResource,
| and the now-playing routes proxy the media servers server side precisely so
| that tokens never reach the browser.
|
*/

Route::get('/auth/status', [AuthController::class, 'status']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/setup', [AuthController::class, 'setup'])->middleware('throttle:6,1');
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::get('/posters', [PosterController::class, 'index']);
Route::get('/posters/{poster}', [PosterController::class, 'show']);
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/sync-status', [ApiController::class, 'checkSyncStatus']);
Route::get('/check-update', [SettingController::class, 'checkUpdate']);

Route::get('/now-playing/{service}', [NowPlayingController::class, 'show'])
    ->name('now-playing.show');
Route::get('/now-playing/{service}/poster', [NowPlayingController::class, 'poster'])
    ->name('now-playing.poster');

// Superseded by /api/now-playing/kodi; kept so existing integrations keep working.
Route::get('/kodi-now-playing', [ApiController::class, 'kodiNowPlaying']);

/*
 * The kiosk display drives the TV's power over HDMI-CEC on a schedule and has
 * no way to sign in, so this stays open. It shells out, but only ever runs
 * cec-client with a literal "on" or "standby" - the command is checked against
 * that list and piped in on stdin, never interpolated into a shell string.
 * Moving the schedule server side would let this be locked down too; see
 * ARCHITECTURE.md.
 */
Route::get('/control-display/{command}', [ApiController::class, 'controlDisplay']);

/*
|--------------------------------------------------------------------------
| Privileged endpoints
|--------------------------------------------------------------------------
|
| Everything that writes, shells out, queues work, or returns credentials.
| Requires either an admin session (the UI) or a Sanctum bearer token (an
| integration), unless DMP_REQUIRE_LOGIN is turned off.
|
*/

Route::middleware('dmp.auth')->group(function () {
    // Returns the full settings row, credentials included, for the admin UI.
    Route::get('/settings/full', [SettingController::class, 'full']);
    Route::put('/settings', [SettingController::class, 'update']);

    Route::post('/posters', [PosterController::class, 'store']);
    Route::put('/posters/{poster}', [PosterController::class, 'update']);
    Route::put('/posters/{id}/{column}', [PosterController::class, 'updateSetting']);
    Route::delete('/posters/{id}', [PosterController::class, 'delete']);
    Route::post('/posters-sort', [PosterController::class, 'sort']);
    Route::post('/show-in-rotation', [PosterController::class, 'showInRotation']);

    Route::get('/service-sections/{service}', [PosterController::class, 'getServiceSections']);

    Route::get('/cache-posters', [ApiController::class, 'cache']);
    Route::post('/now-playing', [ApiController::class, 'dmpBroadcast']);
    Route::post('/stopped', [ApiController::class, 'dmpBroadcast']);

    // Shells out on the host: git pull + composer install.
    Route::get('/update-application', [SettingController::class, 'updateApplication']);
});
