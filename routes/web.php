<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('app');
})->where('any', '.*');

Route::get('/dashboard', function () {
    return view('app');
})->where('any', '.*');

Route::get('/posters', function () {
    return view('app');
})->where('any', '.*');

Route::get('/posters/{any}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/settings', function () {
    return view('app');
})->where('any', '.*');

Route::get('/voting', function () {
    return view('app');
})->where('any', '.*');

//require __DIR__.'/auth.php';
