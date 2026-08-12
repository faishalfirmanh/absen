<?php
return [
    // Endpoint resmi Mekari Qontak untuk interaksi WhatsApp
    'api_url' => env('MEKARI_API_BASE_URL', 'https://api.mekari.com'),
    'client_id' => env('MEKARI_API_CLIENT_ID'),
    'client_secret' => env('MEKARI_API_CLIENT_SECRET'),
    'channel_id' => env('MEKARI_WA_CHANNEL_ID'),
    'va_template_id' => env('MEKARI_VA_TEMPLATE_ID'),
];