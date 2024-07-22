<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponder
{
    public static function success($data): JsonResponse
    {
        return response()->json($data);
    }

    public static function notFound($message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_NOT_FOUND);
    }

    public static function notModified(): JsonResponse
    {
        return response()->json([], Response::HTTP_NOT_MODIFIED);
    }

    public static function internalServerError($message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public static function badRequest($messages): JsonResponse
    {
        return response()->json([
            'message' => 'Validation Failed',
            'errors' => $messages,
        ], Response::HTTP_BAD_REQUEST);
    }
}
