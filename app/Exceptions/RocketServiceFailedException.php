<?php

namespace App\Exceptions;

use App\Helpers\JsonResponder;
use Exception;
use Illuminate\Http\JsonResponse;

class RocketServiceFailedException extends Exception
{
    // This exception will be thrown directly to the application, we can check the log in Laravel log
    public function render(): JsonResponse
    {
        return JsonResponder::internalServerError('Something went wrong with Core Rocket Server');
    }
}
