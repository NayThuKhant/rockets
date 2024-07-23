<?php

namespace App\Services;

use App\Exceptions\InvalidActionOnRocketException;
use App\Exceptions\RocketServiceFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
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
            ]);
    }

    /**
     * @throws RocketServiceFailedException
     */
    public function getRockets($retries = 5): array
    {
        $triedCount = 0;
        $response = null;
        try {
            while ($triedCount < $retries) {
                $response = $this->httpClient->get('rockets');

                if ($response->successful()) {
                    return $response->json();
                }

                $triedCount++;
            }

            throw new RocketServiceFailedException(__METHOD__.' failed with status code '.$response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__.' failed with error '.$exception->getMessage());
        }
    }

    /**
     * @throws RocketServiceFailedException
     * @throws InvalidActionOnRocketException
     */
    public function launchRocket(string $rocketId)
    {
        try {
            $response = $this->httpClient->put("rocket/$rocketId/status/launched");

            if ($response->successful()) {
                return $response->json();
            } elseif ($response->status() === Response::HTTP_NOT_MODIFIED) {
                // Rocket server responds 403 if the rocket is already launched
                throw new InvalidActionOnRocketException("Rocket with given id $rocketId is already launched");
            }

            throw new RocketServiceFailedException(__METHOD__.' failed with status code '.$response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__.' failed with error '.$exception->getMessage());
        }
    }

    /**
     * @throws InvalidActionOnRocketException
     * @throws RocketServiceFailedException
     */
    public function deployRocket(string $rocketId)
    {
        try {
            $response = $this->httpClient->put("rocket/$rocketId/status/deployed");

            if ($response->successful()) {
                return $response->json();
            } elseif ($response->status() === Response::HTTP_NOT_MODIFIED) {
                // Rocket server responds 403 if the rocket is already deployed
                throw new InvalidActionOnRocketException("Rocket with given id $rocketId is already deployed");
            }

            throw new RocketServiceFailedException(__METHOD__.' failed with status code '.$response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__.' failed with error '.$exception->getMessage());
        }
    }

    /**
     * @throws InvalidActionOnRocketException
     * @throws RocketServiceFailedException
     */
    public function cancelRocket(string $rocketId)
    {
        try {
            $response = $this->httpClient->delete("rocket/$rocketId/status/launched");

            if ($response->successful()) {
                return $response->json();
            } elseif ($response->badRequest()) {
                // Rocket server responds bad request/ 400 if the rocket is not launched yet
                throw new InvalidActionOnRocketException("Rocket with given id $rocketId is not launched yet");
            }

            throw new RocketServiceFailedException(__METHOD__.' failed with status code '.$response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__.' failed with error '.$exception->getMessage());
        }
    }

    /**
     * @throws RocketServiceFailedException
     */
    public function getWeather($retries = 5)
    {
        $triedCount = 0;
        $response = null;
        try {
            while ($triedCount < $retries) {
                $response = $this->httpClient->get('weather');

                if ($response->successful()) {
                    return $response->json();
                }

                $triedCount++;
            }

            throw new RocketServiceFailedException(__METHOD__.' failed with status code '.$response->status());
        } catch (ConnectionException $exception) {
            throw new RocketServiceFailedException(__METHOD__.' failed with error '.$exception->getMessage());
        }
    }
}
