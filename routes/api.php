<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\SettingController;
use App\Models\Poster;

Route::get('/posters', [PosterController::class, 'index']);
Route::get('/cache-posters', [PosterController::class, 'cache']);
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
