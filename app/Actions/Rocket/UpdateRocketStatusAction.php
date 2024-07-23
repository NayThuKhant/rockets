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
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->firstWhere('id', $rocketId);

        throw_unless($rocket, new RocketNotFoundException('Rocket not found.'));

        try {
            $updatedRocket = self::updateRocketStatus($rocket, $rocketStatus);
        } catch (RocketStatusNotUpdatedException $exception) {
            // Client may not have the latest data from server and tries to update this
            // Return original rocket data if the status was not updated
            // For better UX
            $updatedRocket = $rocket;
        }

        RocketInformationUpdated::dispatch($updatedRocket);

        return $updatedRocket;
    }

    /**
     * Updates the rocket status based on the provided status.
     *
     *
     *
     * @throws RocketStatusNotUpdatedException
     * @throws InvalidActionOnRocketException
     * @throws Throwable
     */
    private static function updateRocketStatus(array $rocket, RocketStatusEnum $rocketStatus): array
    {
        switch ($rocketStatus) {
            case RocketStatusEnum::LAUNCHED:
                self::checkCurrentStatus($rocket, RocketStatusEnum::LAUNCHED);

                return RocketService::launchRocket($rocket['id']);

            case RocketStatusEnum::DEPLOYED:
                self::checkCurrentStatus($rocket, RocketStatusEnum::DEPLOYED);

                return RocketService::deployRocket($rocket['id']);

            case RocketStatusEnum::CANCELLED:
                self::checkCurrentStatus($rocket, RocketStatusEnum::CANCELLED);

                return RocketService::cancelRocket($rocket['id']);

            default:
                throw new InvalidActionOnRocketException('Given action cannot be applied to rocket.');
        }
    }

    /**
     * Checks the current status of the rocket.
     *
     *
     *
     * @throws RocketStatusNotUpdatedException
     */
    private static function checkCurrentStatus(array $rocket, RocketStatusEnum $expectedStatus): void
    {
        if ($rocket['status'] === $expectedStatus->value) {
            throw new RocketStatusNotUpdatedException("Rocket is already {$expectedStatus->value}.");
        }
    }
}
