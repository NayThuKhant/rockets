<?php

namespace App\Http\Controllers\Api;

use App\Actions\Weather\GetWeatherAction;
use App\Helpers\JsonResponder;
use App\Http\Controllers\Controller;

class WeatherController extends Controller
{
    public function getWeather()
    {
        $weather = GetWeatherAction::handle();

        return JsonResponder::success($weather);
    }
}
