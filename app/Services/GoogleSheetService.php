<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;

class GoogleSheetService
{
    protected GoogleSheets $service;

    public function __construct()
    {
        $client = new GoogleClient();
        $client->setApplicationName(config('services.google_sheets.app_name', 'Laravel Sheet Sync'));
        $client->setScopes([GoogleSheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(config('services.google_sheets.credentials_path'));
        $client->setAccessType('offline');

        $this->service = new GoogleSheets($client);
    }

    /**
     * Ambil semua baris dari sebuah range, contoh: 'Pegawai!A2:E'
     * (mulai dari baris ke-2 supaya header tidak ikut terbaca)
     *
     * @return array<int, array<int, string>>
     */
    public function getRows(string $spreadsheetId, string $range): array
    {
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);

        return $response->getValues() ?? [];
    }
}