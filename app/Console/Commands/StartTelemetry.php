<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartTelemetry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wrapper service for ListenTelemetry with memory limit, This command will automatically restart the ListenTelemetry command if it exceeds the memory limit.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running the command with pid of '.getmypid());
        $memoryLimitInMB = config('rocket.telemetry.memory_limit');
        $command = "php artisan telemetry:listen --memory={$memoryLimitInMB}";

        while (true) {
            $this->info("Running command: {$command}");
            // Using shell_exec instead of Artisan::call intentionally
            $output = shell_exec($command);
            $this->info("Restarting the command since it's exceeding the memory limit");

            $this->warn('Logs from telemetry:listen started');
            $this->info($output);
            $this->warn('Logs from telemetry:listen ended');
        }
    }
}
