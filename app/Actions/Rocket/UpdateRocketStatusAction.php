<?php

namespace App\Actions\Rocket;

use App\Enums\RocketStatusEnum;
use App\Events\RocketInformationUpdated;
use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketNotFoundException;
use App\Exceptions\RocketStatusNotUpdatedException;
use App\Facades\RocketService;
use Throwable;

class UpdateRocketStatusAction
{
    /**
     * Handles the update of rocket status.
     *
     *
     *
     * @throws RocketNotFoundException
     * @throws Throwable
     */
    public static function handle(string $rocketId, RocketStatusEnum $rocketStatus): array
    {


        try {
            $updatedRocket = match ($rocketStatus) {
                RocketStatusEnum::LAUNCHED => RocketService::launchRocket($rocketId),
                RocketStatusEnum::DEPLOYED => RocketService::deployRocket($rocketId),
                RocketStatusEnum::CANCELLED => RocketService::cancelRocket($rocketId),
                default => throw new InvalidActionOnRocketException($rocketId),
            };

        } catch (RocketStatusNotUpdatedException $exception) {
            // RocketStatusNotUpdatedException will be thrown by RocketService, that's why we catch it here
            // We will have to find the actual exception in App/Services/RocketService.php, not under App/Facades/RocketService
            $rockets = RocketService::getRockets();
            $rocket = collect($rockets)->firstWhere('id', $rocketId);

            $updatedRocket = $rocket;
        }

        RocketInformationUpdated::dispatch($updatedRocket);

        return $updatedRocket;
    }
}
