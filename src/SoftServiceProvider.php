<?php

namespace N3m3s7s\Soft;

use Illuminate\Support\ServiceProvider;

class SoftServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => $this->app->configPath().'/'.'soft.php',
        ], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'soft');

        $this->app->bind('soft-image', function () {

            return new SoftImage();

        });

        $this->app->bind('soft-factory', function () {

            return new SoftFactory();

        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides()
    {
        return ['soft-image','soft-factory'];
    }
}