<?php

namespace App\Http\Controllers\Api;

use App\Actions\Rocket\GetRocketsAction;
use App\Actions\Rocket\UpdateRocketStatusAction;
use App\Enums\RocketStatusEnum;
use App\Helpers\JsonResponder;
use App\Http\Controllers\Controller;

class RocketController extends Controller
{
    public function index()
    {
        $rockets = GetRocketsAction::handle();

        return JsonResponder::success($rockets);
    }

    public function launchRocket(string $rocketId)
    {
        $rocket = UpdateRocketStatusAction::handle($rocketId, RocketStatusEnum::LAUNCHED);

        return JsonResponder::success($rocket);
    }

    public function deployRocket(string $rocketId)
    {
        $rocket = UpdateRocketStatusAction::handle($rocketId, RocketStatusEnum::DEPLOYED);

        return JsonResponder::success($rocket);
    }

    public function cancelRocket(string $rocket)
    {
        $rocket = UpdateRocketStatusAction::handle($rocket, RocketStatusEnum::CANCELLED);

        return JsonResponder::success($rocket);
    }
}
