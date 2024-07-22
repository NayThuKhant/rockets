<?php

use App\Http\Controllers\Api\RocketController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'rockets'], function () {
    // Rockets
    Route::get('/', [RocketController::class, 'index']);
    Route::put('/{rocketId}/status/launched', [RocketController::class, 'launchRocket']);
    Route::put('/{rocketId}/status/deployed', [RocketController::class, 'deployRocket']);
    Route::delete('/{rocketId}/status/launched', [RocketController::class, 'cancelRocket']);
});

Route::get('/weather', [WeatherController::class, 'getWeather']);
