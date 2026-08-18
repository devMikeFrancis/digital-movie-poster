<?php

use App\Http\Controllers\ApiController;
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
|--------------------------------------------------------------------------
| Privileged endpoints
|--------------------------------------------------------------------------
|
| Everything that writes, shells out, queues work, or returns credentials.
| Open by default so existing installs keep working; set
| DMP_API_REQUIRE_TOKEN=true to require a Sanctum bearer token on all of them.
|
*/

Route::middleware('dmp.token')->group(function () {
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

    // Both of these shell out on the host.
    Route::get('/control-display/{command}', [ApiController::class, 'controlDisplay']);
    Route::get('/update-application', [SettingController::class, 'updateApplication']);
});
