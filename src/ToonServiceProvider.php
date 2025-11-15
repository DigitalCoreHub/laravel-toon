<?php

namespace DigitalCoreHub\Toon;

use DigitalCoreHub\Toon\Blade\ToonDirective;
use DigitalCoreHub\Toon\Commands\ToonDecodeCommand;
use DigitalCoreHub\Toon\Commands\ToonEncodeCommand;
use DigitalCoreHub\Toon\Debug\ToonCollector;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ToonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/toon.php',
            'toon'
        );

        $this->app->singleton('toon', function ($app) {
            return new Toon;
        });

        // Register Debugbar collector if available
        $this->registerDebugbarCollector();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directive
        Blade::directive('toon', function ($expression) {
            return ToonDirective::compile($expression);
        });

        // Register Log macro (must be in boot() when LogManager is ready)
        $this->registerLogMacro();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/toon.php' => config_path('toon.php'),
            ], 'toon-config');

            $this->commands([
                ToonEncodeCommand::class,
                ToonDecodeCommand::class,
            ]);
        }
    }

    /**
     * Register Debugbar collector if Debugbar is installed.
     */
    protected function registerDebugbarCollector(): void
    {
        if (! class_exists(\Barryvdh\Debugbar\LaravelDebugbar::class)) {
            return;
        }

        if (! app()->bound('debugbar')) {
            return;
        }

        $debugbar = app('debugbar');
        if ($debugbar && method_exists($debugbar, 'addCollector')) {
            $debugbar->addCollector(new ToonCollector);
        }
    }

    /**
     * Register Log macro for TOON logging.
     */
    protected function registerLogMacro(): void
    {
        // Use LogManager directly via app() to ensure macro is registered
        $logManager = app('log');

        // Check if macro already exists
        if (method_exists($logManager, 'hasMacro') && $logManager->hasMacro('toon')) {
            return;
        }

        // Add macro to LogManager if it supports macros
        // Note: LogManager uses Macroable trait in Laravel 10+, but may not in test environments
        if (method_exists($logManager, 'macro')) {
            $logManager->macro('toon', function ($data, string $level = 'info', ?string $channel = null) {
                $toon = app('toon')->encode($data);
                $channel = $channel ?? config('toon.logging_channel', 'stack');

                $message = "TOON Output:\n".$toon;

                if ($channel) {
                    return Log::channel($channel)->{$level}($message);
                }

                return Log::{$level}($message);
            });
        } else {
            // Fallback: Use Log facade directly if LogManager doesn't support macros
            // This ensures the macro works in all Laravel versions
            if (method_exists(Log::class, 'macro')) {
                Log::macro('toon', function ($data, string $level = 'info', ?string $channel = null) {
                    $toon = app('toon')->encode($data);
                    $channel = $channel ?? config('toon.logging_channel', 'stack');

                    $message = "TOON Output:\n".$toon;

                    if ($channel) {
                        return Log::channel($channel)->{$level}($message);
                    }

                    return Log::{$level}($message);
                });
            }
        }
    }
}
