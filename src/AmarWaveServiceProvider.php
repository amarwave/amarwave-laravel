<?php

declare(strict_types=1);

namespace AmarWave\Laravel;

use AmarWave\AmarWave;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel Service Provider for AmarWave.
 *
 * Auto-discovered via composer.json "extra.laravel.providers".
 * Manual registration: add to config/app.php providers array:
 *   AmarWave\Laravel\AmarWaveServiceProvider::class
 */
class AmarWaveServiceProvider extends ServiceProvider
{
    /**
     * Register the AmarWave singleton into the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/amarwave.php',
            'amarwave'
        );

        $this->app->singleton(AmarWave::class, function ($app) {
            /** @var array $cfg */
            $cfg = $app['config']->get('amarwave', []);

            return new AmarWave(
                appKey:    (string) ($cfg['app_key']  ?? ''),
                appSecret: (string) ($cfg['app_secret'] ?? ''),
                cluster:   (string) ($cfg['cluster']  ?? 'default'),
                timeout:   (int)    ($cfg['timeout']  ?? 10),
            );
        });

        $this->app->alias(AmarWave::class, 'amarwave');
    }

    /**
     * Bootstrap services: publish config, register the broadcasting driver.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/amarwave.php' => config_path('amarwave.php'),
            ], 'amarwave-config');
        }

        $this->app->resolving(BroadcastManager::class, function (BroadcastManager $manager) {
            $manager->extend('amarwave', function ($app) {
                return new AmarWaveBroadcaster($app->make(AmarWave::class));
            });
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [AmarWave::class, 'amarwave'];
    }
}
