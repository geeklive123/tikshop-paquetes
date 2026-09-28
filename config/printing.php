<?php

return [
    'driver' => env('PRINT_DRIVER', 'mock'),

    'mock' => [
        'disk' => env('PRINT_MOCK_DISK', 'local'),
        'directory' => env('PRINT_MOCK_DIRECTORY', 'prints'),
    ],

    'agent' => [
        'server_url' => env('PRINT_AGENT_SERVER_URL', env('APP_URL', 'http://localhost')),
        'token' => env('PRINT_AGENT_TOKEN'),
        'poll_seconds' => (int) env('PRINT_AGENT_POLL_SECONDS', 5),
        'online_seconds' => (int) env('PRINT_AGENT_ONLINE_SECONDS', 90),
    ],

    'duplicate_window_seconds' => (int) env('PRINT_DUPLICATE_WINDOW_SECONDS', 120),
];
