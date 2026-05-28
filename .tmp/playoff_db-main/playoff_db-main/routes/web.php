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

Route::get(
    '/mlb-stats',
    [App\Http\Controllers\PlayerController::class, 'index']
)->name('players');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get(
        '/',
        function () {
            return redirect('/dashboard');
        }
    )->name('dashboard_redirect');
    Route::get(
        '/stat/mlb/{page}',
        [App\Http\Controllers\MainContainerController::class, 'index']
    )->name('mlb');

    Route::get(
        '/program/{page}',
        [App\Http\Controllers\MainContainerController::class, 'index']
    )->name('program');

    Route::get(
        '/stat/nba/{page}',
        [App\Http\Controllers\MainContainerController::class, 'index']
    )->name('nba');

    Route::get(
        '/stat/wnba/{page}',
        [App\Http\Controllers\MainContainerController::class, 'index']
    )->name('wnba');
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get(
        '/programs/change-father',
        App\Http\Livewire\Pages\Program\PageNoForm::class
    )->name('programs.change_father');

    Route::get(
        '/program/ProgramSubform/{programId}',
        App\Http\Livewire\Pages\Program\ProgramSubFormsList::class
    )->name('program.sub.forms');

    Route::get(
        '/program/programForm/{programId}',
        App\Http\Livewire\Pages\Program\ProgramFormsList::class
    )->name('program.forms');

    Route::get(
        '/color/{color_group}',
        App\Http\Livewire\Pages\Program\ColorForm::class
    )->name('program.color_form');

    Route::get(
        '/program/program-list',
        App\Http\Livewire\Pages\Program\ProgramFormsList::class
    )->name('program.list');

    Route::get(
        '/program/program-function',
        App\Http\Livewire\Pages\Program\ProgramFunction::class
    )->name('program.function');

    Route::get('/unsure-color', App\Http\Livewire\Pages\Program\UnSureColor::class)
        ->name('program.unsure.color');

    Route::get('/sure-color', App\Http\Livewire\Pages\Program\SureColor::class)
        ->name('program.sure.color');

    Route::get('/test-layout', App\Http\Livewire\Pages\Program\TestLayout::class)
        ->name('program.test');
});


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/contact/contact-location', App\Http\Livewire\Pages\Contact\ContactLocation::class)
        ->name('location');

    Route::get('/contact/contact-departments', App\Http\Livewire\Pages\Contact\ContactDepartment::class)
        ->name('departments');

    Route::get('/contact/contact-employees', App\Http\Livewire\Pages\Contact\ContactEmployees::class)
        ->name('employees');
    Route::get(
        '/logo/{page}',
        [App\Http\Controllers\MainContainerController::class, 'index']
    )->name('logo');
});

// 處理 favicon.ico 請求
Route::get('/favicon.ico', function () {
    return response()->file(public_path('favicon.ico'));
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get(
        '/{page}/',
        [App\Http\Controllers\MainContainerController::class, 'index']
    )->name('dashboard');
});

// 角色和權限管理路由
Route::group(['middleware' => ['auth', 'check.role:admin'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::resource('roles', \App\Http\Controllers\RoleController::class);
    Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
});
