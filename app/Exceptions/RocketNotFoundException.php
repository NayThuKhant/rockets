<?php

namespace App\Exceptions;

use App\Helpers\JsonResponder;
use Exception;
use Illuminate\Http\JsonResponse;

class RocketNotFoundException extends Exception
{
    public function report()
    {
        // Do not report this exception
    }

    public function render(): JsonResponse
    {
        return JsonResponder::notFound('Rocket not found');
    }
}
