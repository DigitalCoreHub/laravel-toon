<?php

namespace DigitalCoreHub\Toon;

use DigitalCoreHub\Toon\Commands\ToonDecodeCommand;
use DigitalCoreHub\Toon\Commands\ToonEncodeCommand;
use Illuminate\Support\ServiceProvider;

class ToonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/toon.php',
            'toon'
        );

        $this->app->singleton('toon', function ($app) {
            return new Toon();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/toon.php' => config_path('toon.php'),
            ], 'toon-config');

            $this->commands([
                ToonEncodeCommand::class,
                ToonDecodeCommand::class,
            ]);
        }
    }
}

