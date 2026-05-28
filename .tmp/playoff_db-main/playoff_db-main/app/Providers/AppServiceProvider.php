<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Dotenv\Dotenv;
use App;
use App\Services\Program\ProgramSubFormDetailServices;
use App\Services\Program\ProgramFunctionServices;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ProgramFunctionServices::class, function ($app) {
            return new ProgramFunctionServices();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
    }
}
