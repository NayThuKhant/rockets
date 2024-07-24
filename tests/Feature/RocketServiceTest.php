<?php

use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketStatusNotUpdatedException;
use App\Facades\RocketService;

describe("Rocket Service", function () {
    // TODO Instead of faking the HTTP client, I will go with the real HTTP client
    // TODO You will need to restart the docker container for Core Rocket System to reset the data as initial state before this test
    /*
    beforeEach(function () {
        Http::fake([
            "*" => Http::response([])
        ]);
    });
    */

    it("should get rockets", function () {
        $rockets = RocketService::getRockets();

        expect($rockets)->toBeArray();

        // Since it's testing the actual HTTP request, we need to ensure that the data is reset to the initial state
        $waitingRockets = collect($rockets)->where("status", "waiting")->count();
        if ($waitingRockets !== count($rockets)) dd("You need to restart the docker containers for ROCKET SERVICE SYSTEM before this test");
    });

    it("should get weather", function () {
        $weather = RocketService::getWeather();

        expect($weather)->toBeArray();
    });

    it("should be able to deploy the waiting rocket", function () {
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->firstWhere("status", "waiting");
        $response = RocketService::deployRocket($rocket["id"]);

        expect($response)->toBeArray()->and($response["status"])->toBe("deployed");
    });

    it("should throw RocketStatusNotUpdatedException when trying to deploy the deployed rocket", function () {
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->firstWhere("status", "deployed");

        expect(fn() => RocketService::deployRocket($rocket['id']))
            ->toThrow(RocketStatusNotUpdatedException::class, 'Rocket status is not updated');
    });

    it("should be able to launch the deployed rocket", function () {
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->firstWhere("status", "deployed");
        $response = RocketService::launchRocket($rocket["id"]);

        expect($response)->toBeArray()->and($response["status"])->toBe("launched");
    });

    it("should throw RocketStatusNotUpdatedException when trying to launch the launched rocket", function () {
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->firstWhere("status", "launched");

        expect(fn() => RocketService::launchRocket($rocket['id']))
            ->toThrow(RocketStatusNotUpdatedException::class, 'Rocket status is not updated');
    });

    it("should be able to cancel the launched rocket", function () {
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->firstWhere("status", "launched");
        $response = RocketService::cancelRocket($rocket["id"]);

        expect($response)->toBeArray()->and($response["status"])->toBe("cancelled");
    });

    it("should throw InvalidActionOnRocketException when trying to cancel the rocket which is not launched yet", function () {
        $rockets = RocketService::getRockets();

        $rocket = collect($rockets)->firstWhere("status", "cancelled");
        expect(fn() => RocketService::cancelRocket($rocket['id']))
            ->toThrow(InvalidActionOnRocketException::class, "Rocket " . $rocket['id'] . " is not launched yet.");
    });
});
