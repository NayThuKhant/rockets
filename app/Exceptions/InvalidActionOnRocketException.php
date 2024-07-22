<?php

namespace App\Exceptions;

use App\Helpers\JsonResponder;
use Exception;
use Illuminate\Http\JsonResponse;

class InvalidActionOnRocketException extends Exception
{
    public function report()
    {
        // Do not report this exception
    }

    public function render(): JsonResponse
    {
        return JsonResponder::badRequest([
            'rocketId' => $this->getMessage(),
        ]);
    }
}
