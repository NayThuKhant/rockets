<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\RocketService
 *
 * @method static array getRockets()
 * @method static array launchRocket(string $rocketId)
 * @method static array deployRocket(string $rocketId)
 * @method static array cancelRocket(string $rocketId)
 * @method static array getWeather()
 */
class RocketService extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\RocketService::class;
    }
}
