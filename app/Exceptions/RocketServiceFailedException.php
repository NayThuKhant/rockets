<?php

namespace App\Exceptions;

use App\Helpers\JsonResponder;
use Exception;
use Illuminate\Http\JsonResponse;

class RocketServiceFailedException extends Exception
{
    public function render(): JsonResponse
    {
        return JsonResponder::internalServerError('Something went wrong with Core Rocket Server');
    }
}
