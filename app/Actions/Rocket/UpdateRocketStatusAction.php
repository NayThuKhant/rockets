<?php

namespace App\Actions\Rocket;

use App\Enums\RocketStatusEnum;
use App\Events\RocketInformationUpdated;
use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketNotFoundException;
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

        switch ($rocketStatus) {
            case RocketStatusEnum::LAUNCHED:
                throw_if($rocket['status'] === RocketStatusEnum::LAUNCHED->value,
                    new InvalidActionOnRocketException('Rocket is already launched'));

                $updatedRocket = RocketService::launchRocket($rocketId);
                break;

            case RocketStatusEnum::DEPLOYED:
                throw_if($rocket['status'] === RocketStatusEnum::DEPLOYED->value,
                    new InvalidActionOnRocketException('Rocket is already deployed'));

                $updatedRocket = RocketService::deployRocket($rocketId);
                break;

            case RocketStatusEnum::CANCELLED:
                throw_if($rocket['status'] === RocketStatusEnum::CANCELLED->value,
                    new InvalidActionOnRocketException('Rocket is already cancelled'));

                $updatedRocket = RocketService::cancelRocket($rocketId);
                break;

            default:
                throw new InvalidActionOnRocketException('Given action cannot be applied for rocket');
        }

        RocketInformationUpdated::dispatch($updatedRocket);

        return $updatedRocket;
    }
}
