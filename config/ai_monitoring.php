<?php

return [
    'api_key' => 'namiroh123',
    'gemini_api_key' => env('GEMINI_API_KEY', ''),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'max_file_mb' => (int) env('AI_MONITORING_MAX_FILE_MB', 25),
    'max_rows' => (int) env('AI_MONITORING_MAX_ROWS', 50000),
    'timeout' => (int) env('AI_MONITORING_TIMEOUT', 120),
    'context_path' => env('AI_MONITORING_CONTEXT_PATH', base_path('contexts/ai_monitoring')),
    'analysis' => [
        'group_share_keywords' => ['share', 'update', 'broadcast', 'posting', 'kirim ke grup', 'sebar', 'bagikan', 'share grup', 'share di grup'],
        'closing_keywords' => ['deal', 'ambil', 'booking', 'book', 'fix', 'jadi ambil', 'ambil paket', 'ambil tiket', 'ambil hotel', 'setuju', 'lanjut', 'transfer', 'dp', 'pelunasan', 'lunas', 'confirm', 'confirmed'],
        'ticket_keywords' => ['ticket', 'tiket', 'flight', 'maskapai', 'lion', 'citilink', 'saudia', 'garuda'],
        'hotel_keywords' => ['hotel', 'makkah', 'mekkah', 'madinah', 'room', 'kamar', 'check in', 'check-in', 'night'],
        'invoice_keywords' => ['invoice', 'inv', 'tagihan', 'buat invoice', 'terbitkan invoice', 'minta invoice', 'billing'],
        'positive_response_keywords' => ['baik', 'siap', 'oke', 'noted', 'sudah', 'akan saya cek', 'saya cek', 'dibantu', 'terima kasih'],
    ],
];
