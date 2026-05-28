<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImagePreviewController;
use App\Livewire\Pages\Admin\ListUser;
use App\Support\ProductRegistry;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['guest'])->group(function () {
    Volt::route('login', 'pages.auth.login')->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('image-preview', ImagePreviewController::class)
    ->middleware(['auth', 'signed'])
    ->name('image-preview.show');

Route::middleware(['auth', 'verified'])->prefix('offorest')->group(function (): void {
    foreach (ProductRegistry::all() as $product) {
        Route::get($product['path'], $product['component'])
            ->middleware('product:'.$product['slug'])
            ->name($product['route_name']);
    }

    Route::get('admin/users', ListUser::class)
        ->middleware('admin')
        ->name('offorest.admin.users');
});

require __DIR__.'/auth.php';
