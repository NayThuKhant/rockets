<?php

namespace App\Actions\Rocket;

use App\Facades\RocketService;

class GetRocketsAction
{
    public static function handle(): array
    {
        return RocketService::getRockets();
    }
}
