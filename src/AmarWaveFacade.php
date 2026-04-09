<?php

declare(strict_types=1);

namespace AmarWave\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel Facade for AmarWave.
 *
 * Auto-registered via composer.json "extra.laravel.aliases".
 * Manual registration: add to config/app.php aliases:
 *   'AmarWave' => AmarWave\Laravel\AmarWaveFacade::class
 *
 * Usage:
 *   use AmarWave\Laravel\AmarWaveFacade as AmarWave;
 *
 *   AmarWave::trigger('my-channel', 'my-event', ['key' => 'value']);
 *   AmarWave::triggerBatch([...]);
 *   AmarWave::authenticate($socketId, $channel);
 *   AmarWave::authenticatePresence($socketId, $channel, $data);
 *
 * @method static array  trigger(string $channel, string $event, mixed $data = null)
 * @method static array  triggerBatch(array $events)
 * @method static string authenticate(string $socketId, string $channel)
 * @method static array  authenticatePresence(string $socketId, string $channel, array $channelData)
 *
 * @see \AmarWave\AmarWave
 */
class AmarWaveFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'amarwave';
    }
}
