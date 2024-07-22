<?php

namespace App\Console\Commands;

use App\Actions\Weather\GetWeatherAction;
use App\Events\WeatherInformationUpdated;
use Illuminate\Console\Command;

class ListenWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch weather information from Rocket Core Service and push back to websocket';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        while (true) {
            // We will send only successful http response to the websocket
            try {
                $weather = GetWeatherAction::handle();
                WeatherInformationUpdated::dispatch($weather);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
