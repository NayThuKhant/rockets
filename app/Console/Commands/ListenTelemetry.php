<?php

namespace App\Console\Commands;

use App\Events\RocketInformationUpdated;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;

class ListenTelemetry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:listen
                            {--memory= : Memory limit in MB for the command to stop and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connects to multiple telemetry TCP servers and processes incoming data, push back to websocket';

    /**
     * The Connector instance.
     */
    private ConnectorInterface $tcpConnector;

    /**
     * The connection instances.
     *
     * @var array<string, ConnectionInterface>
     */
    private array $connections;

    /**
     * Over allow memory usage? If this is true, command will stop reconnecting and exit
     */
    private bool $isOverAllowedMemory = false;

    /**
     * Allowed memory for this command to run.
     */
    private int $allowMemoryInBytes = 50;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->option('memory')) {
            $this->allowMemoryInBytes = $this->option('memory') * 1024 * 1024;
            $this->info('Memory limit set to '.$this->option('memory').' MB');
        }

        $eventLoop = Loop::get();
        $this->tcpConnector = new Connector($eventLoop);

        foreach (config('rocket.telemetry.addresses') as $address) {
            $this->connectToServer($address);
        }

        // Check for allow memory usage, exit the command if exceeds
        if ($this->allowMemoryInBytes) {
            $eventLoop->addPeriodicTimer(5, function () {
                $this->checkMemoryUsage();
            });
        }

        $eventLoop->run();
    }

    /**
     * Connect to a TCP server.
     */
    private function connectToServer(string $address): void
    {
        $this->tcpConnector->connect($address)->then(
            function (ConnectionInterface $connection) use ($address) {
                $this->connections[$address] = $connection;

                $this->info("Connected to $address");

                $connection->on('data', function ($data) {
                    $this->processPacket($data);
                });

                $connection->on('close', function () use ($address) {
                    if (! $this->isOverAllowedMemory) {
                        $this->info("Connection closed to $address. Reconnecting...");
                        $this->reconnect($address);
                    }
                });

                $connection->on('error', function (Exception $exception) use ($address) {

                    $this->reconnect($address);
                    if (! $this->isOverAllowedMemory) {
                        $this->error('Connection error to '.$address.': '.$exception->getMessage().' Reconnecting...');
                        $this->reconnect($address);
                    }
                });
            },
            function (Exception $exception) use ($address) {
                $this->error('Connection failed to '.$address.': '.$exception->getMessage());
                $this->reconnect($address);
            }
        );
    }

    /**
     * Reconnect to the server with a backoff strategy.
     */
    private function reconnect(string $address): void
    {
        $eventLoop = Loop::get();
        $eventLoop->addTimer(5, function () use ($address) {
            $this->connectToServer($address);
        });
    }

    /**
     * Process a single packet.
     */
    private function processPacket(string $packet): void
    {
        if (strlen($packet) < 0x24) {
            Log::error('Received packet too short: '.strlen($packet));

            return;
        }

        $data = unpack('CstartByte/a10rocketID/CpacketNumber/CpacketSize/faltitude/fspeed/facceleration/fthrust/ftemperature/ncrc16/Cdelimiter', $packet);

        if ($this->isValidPacket($data, $packet)) {
            RocketInformationUpdated::dispatch([
                'id' => $data['rocketID'],
                'altitude' => $data['altitude'],
                'speed' => $data['speed'],
                'acceleration' => $data['acceleration'],
                'thrust' => $data['thrust'],
                'temperature' => $data['temperature'],
                'last_updated' => now()->toIso8601String(),
            ]);
        } else {
            Log::error('Received invalid packet from Telemetry Server');
            Log::error(implode(', ', $data));
        }
    }

    /**
     * Validate the packet data.
     */
    private function isValidPacket(array $data, string $packet): bool
    {
        $crcData = substr($packet, 0, 0x21);
        $calculatedCRC = $this->calculateCRC16($crcData);

        return $data['startByte'] === 0x82
            && $data['packetNumber'] > 0 && $data['packetNumber'] <= 0xFF
            && is_float($data['altitude']) && ! is_nan($data['altitude'])
            && is_float($data['speed']) && ! is_nan($data['speed'])
            && is_float($data['acceleration']) && ! is_nan($data['acceleration'])
            && is_float($data['thrust']) && ! is_nan($data['thrust'])
            && is_float($data['temperature']) && ! is_nan($data['temperature'])
            && $calculatedCRC === $data['crc16']
            && $data['delimiter'] === 0x80;
    }

    /**
     * Calculate CRC16/BUYPASS for the given data.
     */
    private function calculateCRC16(string $data): int
    {
        $crc = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x8005;
                } else {
                    $crc = $crc << 1;
                }
            }
        }

        return $crc & 0xFFFF;
    }

    /**
     * Check memory usage and log if it's too high.
     */
    private function checkMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $this->info('Memory usage: '.($memoryUsage / 1024 / 1024).' MB');
        if ($memoryUsage > $this->allowMemoryInBytes) {
            $this->isOverAllowedMemory = true;

            $this->info('Memory usage is above allowed memory: '.($memoryUsage / 1024 / 1024).' MB');
            foreach ($this->connections as $address => $connection) {
                $connection->close();
                $this->info("Closing connection to $address");
            }

            // Special exit code for memory limit exceeded
            exit(99);
        }
    }
}
