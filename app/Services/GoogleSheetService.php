<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;


use Google\Service\Drive;
use Google\Service\Sheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GoogleSheetService
{
    protected GoogleSheets $service;
    protected GoogleClient $client;
    public function __construct()
    {
        $client = new GoogleClient();
        $client->setApplicationName(config('services.google_sheets.app_name', 'Laravel Sheet Sync'));
        $client->setScopes([
            GoogleSheets::SPREADSHEETS_READONLY,
            Drive::DRIVE_READONLY,
        ]);
        $client->setAuthConfig(config('services.google_sheets.credentials_path'));
        $client->setAccessType('offline');

        $this->client = $client;
        $this->service = new GoogleSheets($client);
    }

    /**
     * Ambil semua baris dari sebuah range, contoh: 'Pegawai!A2:E'
     * (mulai dari baris ke-2 supaya header tidak ikut terbaca)
     *
     * @return array<int, array<int, string>>
     */
    // public function getRows(string $spreadsheetId, string $range): array
    // {
    //     $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);

    //     return $response->getValues() ?? [];
    // }

    //atas old saat menggunakan  google seet real

    public function getRows(string $spreadsheetId, string $range): array
    {
        $drive = new Drive($this->client);
        $file = $drive->files->get($spreadsheetId, ['fields' => 'id, name, mimeType']);

        return $file->getMimeType() === 'application/vnd.google-apps.spreadsheet'
            ? $this->getRowsViaSheetsApi($spreadsheetId, $range)
            : $this->getRowsViaXlsxExport($spreadsheetId, $range);
    }

    protected function getRowsViaSheetsApi(string $spreadsheetId, string $range): array
    {
        $sheets = new Sheets($this->client);
        return $sheets->spreadsheets_values->get($spreadsheetId, $range)->getValues() ?? [];
    }

    protected function getRowsViaXlsxExport(string $spreadsheetId, string $range): array
    {
        [$sheetName, $cellRange] = explode('!', $range); // "Sheet1!B37:R" -> "Sheet1", "B37:R"

        $tmpPath = $this->downloadXlsxToTemp($spreadsheetId);

        $spreadsheet = IOFactory::load($tmpPath);
        $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        // Lengkapi baris akhir kalau range terbuka (mis. "B37:R" tanpa angka akhir)
        if (!preg_match('/\d+$/', $cellRange)) {
            $cellRange .= $sheet->getHighestDataRow();
        }

        // formatData = true PENTING: biar tanggal & angka keluar sebagai string
        // ter-format persis seperti tampilan sel, mendekati perilaku Sheets API
        $rows = $sheet->rangeToArray($cellRange, null, true, true);

        @unlink($tmpPath);

        return $rows;
    }

    protected function downloadXlsxToTemp(string $fileId): string
    {
        $drive = new Drive($this->client);
        $response = $drive->files->get($fileId, ['alt' => 'media']);

        $dir = storage_path('app/tmp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpPath = $dir . '/gsheet_' . $fileId . '_' . uniqid() . '.xlsx';
        file_put_contents($tmpPath, $response->getBody()->getContents());

        return $tmpPath;
    }
}