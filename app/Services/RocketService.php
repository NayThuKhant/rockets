<?php

namespace App\Services;

use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketServiceFailedException;
use App\Exceptions\RocketStatusNotUpdatedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

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
            ]);
    }

    /**
     * Handles API request and retries if necessary.
     *
     * @throws RocketServiceFailedException
     */
    private function handleRequestWithRetires(callable $request, int $retries = 5): array
    {
        $triedCount = 0;
        $response = null;
        while ($triedCount < $retries) {
            try {
                $response = $request();
                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() !== Response::HTTP_SERVICE_UNAVAILABLE) {
                    // Cancel retries if the response is not 503
                    break;
                }

                $triedCount++;
            } catch (ConnectionException $exception) {
                throw new RocketServiceFailedException(__METHOD__ . ' failed with error ' . $exception->getMessage());
            }
        }

        // If the retries count greater than the given, or we receive other response codes
        throw new RocketServiceFailedException(__METHOD__ . ' failed with status code ' . $response->status());
    }

    /**
     * @throws RocketServiceFailedException
     */
    public function getRockets($retries = 5): array
    {
        return $this->handleRequestWithRetires(function () {
            return $this->httpClient->get('rockets');
        }, $retries);
    }

    /**
     * Handles rocket status update requests.
     *
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     */
    private function updateRocketStatus(string $rocketId, string $status, string $errorMessage): array
    {
        try {
            $response = $this->httpClient->put("rocket/$rocketId/status/$status");

            if ($response->successful()) {
                return $response->json();
            } elseif ($response->status() === Response::HTTP_NOT_MODIFIED) {
                throw new RocketStatusNotUpdatedException($errorMessage);
            }

            throw new RocketServiceFailedException(__METHOD__ . ' failed with status code ' . $response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__ . ' failed with error ' . $exception->getMessage());
        }
    }

    /**
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     */
    public function launchRocket(string $rocketId): array
    {
        return $this->updateRocketStatus($rocketId, 'launched', "Rocket with given id $rocketId is already launched");
    }

    /**
     * @throws RocketServiceFailedException
     * @throws RocketStatusNotUpdatedException
     */
    public function deployRocket(string $rocketId): array
    {
        return $this->updateRocketStatus($rocketId, 'deployed', "Rocket with given id $rocketId is already deployed");
    }

    /**
     * @throws InvalidActionOnRocketException
     * @throws RocketServiceFailedException
     */
    public function cancelRocket(string $rocketId): array
    {
        try {
            $response = $this->httpClient->delete("rocket/$rocketId/status/launched");

            if ($response->successful()) {
                return $response->json();
            } elseif ($response->badRequest()) {
                throw new InvalidActionOnRocketException("Rocket with given id $rocketId is not launched yet");
            }

            throw new RocketServiceFailedException(__METHOD__ . ' failed with status code ' . $response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__ . ' failed with error ' . $exception->getMessage());
        }
    }

    /**
     * @throws RocketServiceFailedException
     */
    public function getWeather($retries = 5): array
    {
        return $this->handleRequestWithRetires(function () {
            return $this->httpClient->get('weather');
        }, $retries);
    }
}
