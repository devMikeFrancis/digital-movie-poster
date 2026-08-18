<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Read-only endpoints
|--------------------------------------------------------------------------
|
| The kiosk display polls these on a timer and has no way to authenticate,
| so they stay open regardless of DMP_API_REQUIRE_TOKEN.
|
*/

Route::get('/posters', [PosterController::class, 'index']);
Route::get('/posters/{poster}', [PosterController::class, 'show']);
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/sync-status', [ApiController::class, 'checkSyncStatus']);
Route::get('/kodi-now-playing', [ApiController::class, 'kodiNowPlaying']);
Route::get('/check-update', [SettingController::class, 'checkUpdate']);

/*
|--------------------------------------------------------------------------
| Privileged endpoints
|--------------------------------------------------------------------------
|
| Everything that writes, shells out, or queues work. Open by default so that
| existing installs keep working; set DMP_API_REQUIRE_TOKEN=true to require a
| Sanctum bearer token on all of them.
|
*/

Route::middleware('dmp.token')->group(function () {
    Route::post('/posters', [PosterController::class, 'store']);
    Route::put('/posters/{poster}', [PosterController::class, 'update']);
    Route::put('/posters/{id}/{column}', [PosterController::class, 'updateSetting']);
    Route::delete('/posters/{id}', [PosterController::class, 'delete']);
    Route::post('/posters-sort', [PosterController::class, 'sort']);
    Route::post('/show-in-rotation', [PosterController::class, 'showInRotation']);

    Route::put('/settings', [SettingController::class, 'update']);
    Route::get('/service-sections/{service}', [PosterController::class, 'getServiceSections']);

    Route::get('/cache-posters', [ApiController::class, 'cache']);
    Route::post('/now-playing', [ApiController::class, 'dmpBroadcast']);
    Route::post('/stopped', [ApiController::class, 'dmpBroadcast']);

    // Both of these shell out on the host.
    Route::get('/control-display/{command}', [ApiController::class, 'controlDisplay']);
    Route::get('/update-application', [SettingController::class, 'updateApplication']);
});
