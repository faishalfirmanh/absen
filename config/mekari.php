<?php
return [
    // Endpoint resmi Mekari Qontak untuk interaksi WhatsApp
    'api_url' => env('MEKARI_API_BASE_URL', 'https://api.mekari.com'),
    'client_id' => env('MEKARI_API_CLIENT_ID'),
    'client_secret' => env('MEKARI_API_CLIENT_SECRET'),
    'client_id_2' => env('MEKARI_CLIENT_ID_2'),
    'client_secret_2' => env('MEKARI_API_CLIENT_SECRET_2'),
    'channel_id' => env('MEKARI_WA_CHANNEL_ID'),
    'va_template_id' => env('MEKARI_VA_TEMPLATE_ID'),
    'omnichannel_token' => env('MEKARI_TOKEN'),
    'chat_base_url' => 'https://service-chat.qontak.com/api/open',
    'template_2' => env('MEKARI_TEMPLATE_2')
];