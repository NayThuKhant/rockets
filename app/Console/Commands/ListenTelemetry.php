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
    protected $signature = 'telemetry:listen {--memory= : Memory limit in MB for the command to stop and exit}';

    protected $description = 'Connects to multiple telemetry TCP servers and processes incoming data, push back to websocket';

    private ConnectorInterface $tcpConnector;

    private array $connections = [];

    private bool $isOverAllowedMemory = false;

    private int $allowMemoryInBytes = 50 * 1024 * 1024;

    public function handle(): void
    {
        $this->initializeMemoryLimit();
        $eventLoop = Loop::get();
        $this->tcpConnector = new Connector($eventLoop);

        foreach (config('rocket.telemetry.addresses') as $address) {
            $this->connectToServer($address);
        }

        $this->monitorMemoryUsage($eventLoop);
        $eventLoop->run();
    }

    private function initializeMemoryLimit(): void
    {
        if ($memoryLimit = $this->option('memory')) {
            $this->allowMemoryInBytes = $memoryLimit * 1024 * 1024;
            $this->info("Memory limit set to $memoryLimit MB");
        }
    }

    private function connectToServer(string $address): void
    {
        $this->tcpConnector->connect($address)->then(
            function (ConnectionInterface $connection) use ($address) {
                $this->handleSuccessfulConnection($address, $connection);
            },
            function (Exception $exception) use ($address) {
                $this->handleFailedConnection($address, $exception);
            }
        );
    }

    private function handleSuccessfulConnection(string $address, ConnectionInterface $connection): void
    {
        $this->connections[$address] = $connection;
        $this->info("Connected to $address");

        $connection->on('data', fn ($packet) => $this->processPacket($packet));
        $connection->on('close', fn () => $this->handleConnectionClose($address));
        $connection->on('error', fn (Exception $exception) => $this->handleConnectionError($address, $exception));
    }

    private function handleFailedConnection(string $address, Exception $exception): void
    {
        Log::error("Connection error to $address: {$exception->getMessage()}, reconnecting...");
        $this->error("Connection failed to $address: {$exception->getMessage()}, reconnecting ...");
        $this->reconnect($address);
    }

    private function handleConnectionClose(string $address): void
    {
        if (! $this->isOverAllowedMemory) {
            $this->info("Connection closed to $address");
            $this->reconnect($address);
        }
    }

    private function handleConnectionError(string $address, Exception $exception): void
    {
        if (! $this->isOverAllowedMemory) {
            Log::error("Connection error to $address: {$exception->getMessage()} Reconnecting...");
            $this->error("Connection error to $address: {$exception->getMessage()} Reconnecting...");
            $this->reconnect($address);
        }
    }

    private function reconnect(string $address): void
    {
        Loop::get()->addTimer(5, fn () => $this->connectToServer($address));
    }

    private function processPacket(string $packet): void
    {
        if (strlen($packet) < 0x24) {
            Log::error('Received packet too short: '.strlen($packet));

            return;
        }

        $data = unpack('CstartByte/a10rocketID/CpacketNumber/CpacketSize/faltitude/fspeed/facceleration/fthrust/ftemperature/ncrc16/Cdelimiter', $packet);

        if ($this->isValidPacket($data, $packet)) {
            RocketInformationUpdated::dispatch($this->mapPacketData($data));
        } else {
            Log::error('Received invalid packet from Telemetry Server');
            Log::error(implode(', ', $data));
        }
    }

    private function isValidPacket(array $data, string $packet): bool
    {
        $crcData = substr($packet, 0, 0x21);
        $calculatedCRC = $this->calculateCRC16($crcData);

        return $data['startByte'] === 0x82
            && $data['packetNumber'] > 0 && $data['packetNumber'] <= 0xFF
            && $this->isValidFloat($data['altitude'])
            && $this->isValidFloat($data['speed'])
            && $this->isValidFloat($data['acceleration'])
            && $this->isValidFloat($data['thrust'])
            && $this->isValidFloat($data['temperature'])
            && $calculatedCRC === $data['crc16']
            && $data['delimiter'] === 0x80;
    }

    private function isValidFloat($value): bool
    {
        return is_float($value) && ! is_nan($value);
    }

    private function calculateCRC16(string $data): int
    {
        $crc = 0;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x8005 : $crc << 1;
            }
        }

        return $crc & 0xFFFF;
    }

    private function mapPacketData(array $data): array
    {
        return [
            'id' => $data['rocketID'],
            'altitude' => $data['altitude'],
            'speed' => $data['speed'],
            'acceleration' => $data['acceleration'],
            'thrust' => $data['thrust'],
            'temperature' => $data['temperature'],
            'last_updated' => now()->toIso8601String(),
        ];
    }

    private function monitorMemoryUsage($eventLoop): void
    {
        if ($this->allowMemoryInBytes) {
            $eventLoop->addPeriodicTimer(5, function () {
                $this->checkMemoryUsage();
            });
        }
    }

    private function checkMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);

        if ($memoryUsage > $this->allowMemoryInBytes) {
            $this->isOverAllowedMemory = true;
            $this->info('Memory usage is above allowed memory: '.($memoryUsage / 1024 / 1024).' MB and the command will close all connections');
            $this->closeAllConnections();
            exit();
        }
    }

    private function closeAllConnections(): void
    {
        foreach ($this->connections as $address => $connection) {
            $connection->close();
            $this->info("Closing connection to $address");
        }
    }
}
