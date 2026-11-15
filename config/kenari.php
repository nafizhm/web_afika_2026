<?php

return [
    'api_key' => env('KENARI_API_KEY', ''),
    'model' => env('KENARI_MODEL', ''),
    'base_url' => env('KENARI_BASE_URL', 'https://kenari.id/v1'),
    'timeout' => (int) env('KENARI_TIMEOUT', 30),
];
