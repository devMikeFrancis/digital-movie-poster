<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\SettingController;

Route::get('/posters', [PosterController::class, 'index']);
Route::post('/show-in-rotation', [PosterController::class, 'showInRotation']);
Route::get('/cache-posters', [ApiController::class, 'cache']);
Route::get('/sync-status', [ApiController::class, 'checkSyncStatus']);
Route::get('/control-display/{command}', [ApiController::class, 'controlDisplay']);

Route::get('/settings', [SettingController::class, 'index']);
Route::put('/settings', [SettingController::class, 'update']);
Route::post('/posters', [PosterController::class, 'store']);
Route::post('/posters-sort', [PosterController::class, 'sort']);
Route::get('/posters/{poster}', [PosterController::class, 'show']);
Route::put('/posters/{poster}', [PosterController::class, 'update']);
Route::put('/posters/{id}/{column}', [PosterController::class, 'updateSetting']);
Route::delete('/posters/{id}', [PosterController::class, 'delete']);
Route::get('/update-application', [SettingController::class, 'updateApplication']);
Route::get('/check-update', [SettingController::class, 'checkUpdate']);
Route::get('/service-sections/{service}', [PosterController::class, 'getServiceSections']);
Route::get('/kodi-now-playing', [ApiController::class, 'kodiNowPlaying']);
