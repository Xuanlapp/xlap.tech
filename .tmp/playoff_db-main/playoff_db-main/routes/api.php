<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/mlb/{id}/{name}/{team}/{full_pos}/{status}', [App\Http\Controllers\MLBPlayerController::class, 'show']);

Route::get('/nba/{id}/{name}/{team}/{full_pos}/{status}', [App\Http\Controllers\NBAPlayerController::class, 'show']);

Route::get('/wnba/{id}/{name}/{team}/{full_pos}/{status}', [App\Http\Controllers\WNBAPlayerController::class, 'show']);

Route::get('/basketball_logo/{team}/{year}',
    [App\Http\Controllers\BasketballLogoApiController::class, 'show'])->name('api.basketball_logo');
