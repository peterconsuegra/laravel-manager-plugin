<?php

namespace Pete\LaravelManager;

use Illuminate\Support\ServiceProvider;

class LaravelManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('laravel-manager', function ($app) {
            return new Manager; // Optional small facade/class if you want.
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'laravel-manager');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/views' => resource_path('views/vendor/laravel-manager'),
            ], 'laravel-manager-views');
        }
    }
}
