<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\SettingController;

Route::get('/posters', [PosterController::class, 'index']);
Route::get('/cache-posters', [PosterController::class, 'cache']);
Route::get('/control-display/{command}', [ApiController::class, 'controlDisplay']);

Route::get('/settings', [SettingController::class, 'index']);
Route::put('/settings', [SettingController::class, 'update']);
Route::post('/posters', [PosterController::class, 'store']);
Route::post('/posters-sort', [PosterController::class, 'sort']);
Route::get('/posters/{id}', [PosterController::class, 'show']);
Route::put('/posters/{id}', [PosterController::class, 'update']);
Route::put('/posters/{id}/update-rotation', [PosterController::class, 'updateShowInRotation']);
Route::put('/posters/{id}/{column}', [PosterController::class, 'updateSetting']);
Route::delete('/posters/{id}', [PosterController::class, 'delete']);
