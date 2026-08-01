<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportJamaahFromGoogleSheet extends Command
{
    /**
     * php artisan jamaah:import-sheet
     */
    protected $signature = 'jamaah:import-sheet';

    protected $description = 'Import data jamaah dari semua sheet bernama bulan di Google Sheet ke file JSON per bulan di folder data_jamaah';

    /**
     * Mapping nama bulan dalam Bahasa Indonesia.
     *
     * @var array<int,string>
     */
    protected array $namaBulanIndo = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function handle(): int
    {
        // Tahun yang dipakai untuk semua file JSON = tahun berjalan.
        // Catatan: kalau ada sheet bulan yang sebenarnya mewakili tahun berbeda
        // (mis. sheet JANUARI untuk tahun depan), nilai ini perlu disesuaikan manual.
        $tahun = (int) now()->format('Y');

        // Reverse map: "JULI" => "Juli", "AGUSTUS" => "Agustus", dst.
        $bulanUpperToNama = array_combine(
            array_map('strtoupper', $this->namaBulanIndo),
            $this->namaBulanIndo
        );

        // ------------------------------------------------------------------
        // 1. Ambil ID Google Sheet dari konfigurasi
        // ------------------------------------------------------------------
        $sheetId = config('services.google_sheets.jamaah_id');

        if (empty($sheetId)) {
            $this->error('GOOGLE_SHEET_JAMAAH_ID belum di-set di file .env');
            return self::FAILURE;
        }

        $exportUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=xlsx";
        $tempPath = storage_path('app/tmp_jamaah_sheet_' . now()->timestamp . '.xlsx');

        // ------------------------------------------------------------------
        // 2. Download file xlsx dari Google Sheet (satu kali, seluruh workbook)
        // ------------------------------------------------------------------
        try {
            $response = Http::timeout(60)->get($exportUrl);
        } catch (Throwable $e) {
            $this->error('Gagal mengunduh Google Sheet: ' . $e->getMessage());
            Log::error('[jamaah:import-sheet] Gagal download sheet', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        if (!$response->ok()) {
            $this->error('Gagal mengunduh Google Sheet, HTTP status: ' . $response->status());
            $this->line('Pastikan Google Sheet sudah di-share sebagai "Anyone with the link - Viewer".');
            return self::FAILURE;
        }

        file_put_contents($tempPath, $response->body());

        // ------------------------------------------------------------------
        // 3. Load workbook
        // ------------------------------------------------------------------
        try {
            $spreadsheet = IOFactory::load($tempPath);
        } catch (Throwable $e) {
            $this->error('Gagal membaca file xlsx: ' . $e->getMessage());
            Log::error('[jamaah:import-sheet] Gagal load xlsx', ['error' => $e->getMessage()]);
            @unlink($tempPath);
            return self::FAILURE;
        }

        // ------------------------------------------------------------------
        // 4. Pastikan folder data_jamaah ada (setara level folder app/)
        // ------------------------------------------------------------------
        $folderPath = base_path('data_jamaah');

        if (!is_dir($folderPath)) {
            if (!mkdir($folderPath, 0755, true) && !is_dir($folderPath)) {
                $this->error("Gagal membuat folder: {$folderPath}");
                @unlink($tempPath);
                return self::FAILURE;
            }
            $this->info("Folder 'data_jamaah' berhasil dibuat: {$folderPath}");
        }

        // ------------------------------------------------------------------
        // 5. Loop semua sheet di workbook, hanya proses sheet bernama bulan
        // ------------------------------------------------------------------
        $ringkasan = [];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $key = strtoupper(trim($sheetName));

            if (!isset($bulanUpperToNama[$key])) {
                $this->comment(Carbon::now() . " | Melewati sheet '{$sheetName}' (bukan nama bulan).");
                continue;
            }

            $namaBulan = $bulanUpperToNama[$key]; // ex: "Juli"
            $sheet = $spreadsheet->getSheetByName($sheetName);

            $this->info(Carbon::now() . "| Memproses sheet '{$sheetName}'...");

            // Khusus bulan Juli mulai baris 767, bulan lain mulai baris 2.
            $startRow = ($key === 'JULI') ? 767 : 2;
            $highestRow = $sheet->getHighestRow();

            $dataJamaah = [];
            $usedIds = [];

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $paket = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
                $nama = trim((string) $sheet->getCell('D' . $row)->getFormattedValue());
                $noHp = trim((string) $sheet->getCell('G' . $row)->getFormattedValue());

                // Lewati baris yang kosong (tidak ada paket maupun nama)
                if ($paket === '' && $nama === '') {
                    continue;
                }

                $dataJamaah[] = [
                    'id_jamaah' => $this->generateUniqueId($usedIds),
                    'paket' => $paket,
                    'nama_jamaah' => $nama,
                    'no_hp' => $noHp,
                ];
            }

            // Pastikan file json bulan ini ada, jika belum akan dibuat
            $fileName = 'jamaah_' . strtolower($namaBulan) . '_' . $tahun . '.json';
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

            if (!file_exists($filePath)) {
                touch($filePath);
                $this->info("File '{$fileName}' belum ada, berhasil dibuat.");
            }

            // Tulis (overwrite) hasil import ke file json
            file_put_contents(
                $filePath,
                json_encode($dataJamaah, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            $ringkasan[] = [
                'sheet' => $sheetName,
                'file' => $fileName,
                'total' => count($dataJamaah),
            ];

            $this->info(Carbon::now() . "-> {$fileName}: " . count($dataJamaah) . ' data jamaah tersimpan.');

            Log::info(Carbon::now() . ' | [jamaah:import-sheet] Sheet selesai diproses', [
                'sheet' => $sheetName,
                'file' => $filePath,
                'total' => count($dataJamaah),
            ]);
        }

        @unlink($tempPath);

        if (empty($ringkasan)) {
            $this->warn('Tidak ada sheet bernama bulan yang ditemukan di Google Sheet ini.');
            return self::FAILURE;
        }

        $this->info('Selesai. Total ' . count($ringkasan) . ' sheet bulan berhasil di-import:');
        $this->table(['Sheet', 'File JSON', 'Jumlah Data'], $ringkasan);

        return self::SUCCESS;
    }

    /**
     * Generate id_jamaah unik berupa random number string (10 digit),
     * dijamin tidak duplikat dalam satu sheet yang sedang diproses.
     */
    protected function generateUniqueId(array &$usedIds): string
    {
        do {
            $id = (string) random_int(1000000000, 9999999999);
        } while (in_array($id, $usedIds, true));

        $usedIds[] = $id;

        return $id;
    }
}