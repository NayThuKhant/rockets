<?php

namespace App\Services;

use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketNotFoundException;
use App\Exceptions\RocketServiceFailedException;
use App\Exceptions\RocketStatusNotUpdatedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class RocketService
{
    private PendingRequest $httpClient;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->httpClient = Http::baseUrl(config('rocket.base_uri'))
            ->withHeaders([
                'X-API-KEY' => config('rocket.api_key'),
            ])->retry(5, 0, function ($exception) {
                // If the exception is a RequestException and the status code is 503, retry the request
                return $exception instanceof RequestException && $exception->getCode() === Response::HTTP_SERVICE_UNAVAILABLE;
            }, false);
    }


    /**
     * Handles rocket status update requests
     *
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     * @throws InvalidActionOnRocketException
     * @throws RocketNotFoundException
     */
    private function handleHttpRequest(callable $request): array
    {
        try {
            $response = $request();

            if ($response->successful()) {
                return $response->json();
            } elseif ($response->status() === Response::HTTP_NOT_MODIFIED) {
                throw new RocketStatusNotUpdatedException("Rocket status is not updated");
            } elseif ($response->badRequest()) {
                throw new InvalidActionOnRocketException($response->json()["message"]);
            } else if ($response->notFound()) {
                throw new RocketNotFoundException("Rocket not found");
            }

            throw new RocketServiceFailedException("Request to Rocket Core Server failed with status code " . $response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException($exception->getMessage());
        }
    }


    /**
     * @throws InvalidActionOnRocketException
     * @throws RocketStatusNotUpdatedException
     * @throws RocketServiceFailedException
     * @throws RocketNotFoundException
     */
    public function getRockets(): array
    {
        return $this->handleHttpRequest(
            function () {
                return $this->httpClient->get('rockets');
            },
        );
    }

    /**
     * @throws RocketStatusNotUpdatedException
     * @throws InvalidActionOnRocketException
     * @throws RocketServiceFailedException
     * @throws RocketNotFoundException
     */
    public function getWeather(): array
    {
        return $this->handleHttpRequest(
            function () {
                return $this->httpClient->get('weather');
            },
        );
    }


    /**
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     * @throws InvalidActionOnRocketException
     * @throws RocketNotFoundException
     */
    public function launchRocket(string $rocketId): array
    {
        return $this->handleHttpRequest(
            function () use ($rocketId) {
                return $this->httpClient->put("rocket/$rocketId/status/launched");
            },
        );
    }

    /**
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     * @throws InvalidActionOnRocketException
     * @throws RocketNotFoundException
     */
    public function deployRocket(string $rocketId): array
    {
        return $this->handleHttpRequest(
            function () use ($rocketId) {
                return $this->httpClient->put("rocket/$rocketId/status/deployed");
            },
        );
    }

    /**
     * @throws InvalidActionOnRocketException
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     * @throws RocketNotFoundException
     */
    public function cancelRocket(string $rocketId): array
    {
        return $this->handleHttpRequest(
            function () use ($rocketId) {
                return $this->httpClient->delete("rocket/$rocketId/status/launched");
            },
        );
    }
}
