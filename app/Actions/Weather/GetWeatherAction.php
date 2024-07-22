<?php

namespace App\Actions\Weather;

use App\Facades\RocketService;

class GetWeatherAction
{
    public static function handle(): array
    {
        return RocketService::getWeather();
    }
}
