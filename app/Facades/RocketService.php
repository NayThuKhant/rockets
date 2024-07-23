<?php

namespace App\Facades;

use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketNotFoundException;
use App\Exceptions\RocketServiceFailedException;
use App\Exceptions\RocketStatusNotUpdatedException;
use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\RocketService
 *
 * @method static array getWeather()
 * @method static array getRockets()
 * @method static array launchRocket(string $rocketId)
 * @method static array deployRocket(string $rocketId)
 * @method static array cancelRocket(string $rocketId)
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
