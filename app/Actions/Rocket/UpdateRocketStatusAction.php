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
     * @throws RocketNotFoundException
     * @throws Throwable
     */
    public static function handle($rocketId, RocketStatusEnum $rocketStatus): array
    {
        $rockets = RocketService::getRockets();
        $rocket = collect($rockets)->where('id', $rocketId)->first();

        // Rocket is not found on rocket server
        throw_unless($rocket, new RocketNotFoundException());

        try {
            switch ($rocketStatus) {
                case RocketStatusEnum::LAUNCHED:
                    throw_if($rocket['status'] === RocketStatusEnum::LAUNCHED->value,
                        new RocketStatusNotUpdatedException('Rocket is already launched'));

                    $updatedRocket = RocketService::launchRocket($rocketId);
                    break;

                case RocketStatusEnum::DEPLOYED:
                    throw_if($rocket['status'] === RocketStatusEnum::DEPLOYED->value,
                        new RocketStatusNotUpdatedException('Rocket is already deployed'));

                    $updatedRocket = RocketService::deployRocket($rocketId);
                    break;

                case RocketStatusEnum::CANCELLED:
                    throw_if($rocket['status'] === RocketStatusEnum::CANCELLED->value,
                        new RocketStatusNotUpdatedException('Rocket is already cancelled'));

                    $updatedRocket = RocketService::cancelRocket($rocketId);
                    break;

                default:
                    throw new InvalidActionOnRocketException('Given action cannot be applied for rocket');
            }
        } catch (RocketStatusNotUpdatedException $exception) {
            // When there is an invalid action on rocket, the response should be original rocket value
            // This is optional, and to provide smooth user experience and to mention that resource has an update that is not applied yet

            // We may also add some flag to mention that the server is updated
            // I don't use 304 here, just to make sure that the client receives the updates from server
            $updatedRocket = $rocket;
        }


        RocketInformationUpdated::dispatch($updatedRocket);

        return $updatedRocket;
    }
}
