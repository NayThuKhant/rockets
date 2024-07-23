<?php

use App\Facades\RocketService;
use Illuminate\Support\Facades\Http;

describe("Rocket Service", function () {
    beforeEach(function () {
        Http::fake([
            "*" => Http::response([])
        ]);
    });

    it("should get rockets", function () {
        $rockets = RocketService::getRockets();

        expect($rockets)->toBeArray();
    });

    it("should get weather", function () {
        $weather = RocketService::getWeather();

        expect($weather)->toBeArray();
    });

    it("should launch rocket", function () {

        $response = RocketService::launchRocket("ABCDEFG");

        expect($response)->toBeArray();
    });
});
