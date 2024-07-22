<?php

return [
    //  Rocket Http Service Configuration
    'base_uri' => env('ROCKET_BASE_URI', 'http://localhost:5000'),
    'api_key' => env('ROCKET_API_KEY', 'API_KEY_1'),

    // Telemetry Listener
    'telemetry' => [
        'memory_limit' => intval(env('TELEMETRY_MEMORY_LIMIT', 50)),
        'addresses' => explode(',',
            env('TELEMETRY_ADDRESSES',
                'localhost:4000,localhost:4001,localhost:4002,localhost:4003,localhost:4004,localhost:4005,localhost:4006,localhost:4007,localhost:4008')),
    ],
];
