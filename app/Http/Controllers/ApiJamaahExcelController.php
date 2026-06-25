<?php

namespace App\Http\Controllers;

use App\Models\Alhidayah\DataJamaah;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use ZerosDev\NikReader\Reader;
use Carbon\Carbon;
use Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\JamaahAlamatImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Style\Fill;
class ApiJamaahExcelController extends Controller
{
    //

    use ApiResponse;

    public function cariJamaah(Request $request)
    {

        $cariJamaah = DataJamaah::where('nama_jamaah', $request->jamaah)->first();

        $nik = '3502200101910001';
        $reader = new Reader();
        $result = $reader->read($nik);

        if ($result->valid) {
            echo $result->province;
            echo $result->city;
            echo $result->subdistrict;
            echo $result->postal_code;
        }
    }

    public function indexAlamat(Request $request)
    {
        return view('jamaah');
    }

    public function proses(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,xlsm',
        ]);

        // Simpan file upload sementara
        $uploadedFile = $request->file('file_excel');
        $tempPath = $uploadedFile->storeAs(
            'temp',
            'jamaah_raw_' . time() . '.' . $uploadedFile->getClientOriginalExtension(),
            'local'
        );

        $fullTempPath = storage_path('app/' . $tempPath);

        // Load file excel asli
        $spreadsheet = IOFactory::load($fullTempPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Ambil row terakhir
        $highestRow = $sheet->getHighestRow();

        // Kolom yang dipakai:
        // E = No Identitas
        // F = Nama Paspor
        // L = Alamat
        $colNoIdentitas = 'E';
        $colNamaPaspor = 'F';
        $colAlamat = 'L';

        // Reader NIK
        $reader = new Reader();

        for ($row = 2; $row <= $highestRow; $row++) {
            $namaPaspor = trim((string) $sheet->getCell($colNamaPaspor . $row)->getValue());
            $noIdentitas = trim((string) $sheet->getCell($colNoIdentitas . $row)->getValue());
            $alamatLama = trim((string) $sheet->getCell($colAlamat . $row)->getValue());

            // Hanya update jika alamat kosong atau "-"
            if ($alamatLama !== '' && $alamatLama !== '-') {
                continue;
            }

            $alamatBaru = null;

            /**
             * =========================================================
             * PRIORITAS 1: Cari dari tabel DataJamaah berdasarkan nama
             * =========================================================
             */
            if ($namaPaspor !== '') {
                $jamaah = DataJamaah::with(['getProv', 'getCity', 'getKec', 'getVillage'])
                    ->where('nama_jamaah', $namaPaspor)
                    ->whereNotNull('location_prov')
                    ->first();

                if ($jamaah) {
                    $bagianAlamat = array_filter([
                        "Prov. " . optional($jamaah->getProv)->name,
                        "Kota. " . optional($jamaah->getCity)->name,
                        "Kec. " . optional($jamaah->getKec)->name,
                        "Desa. " . optional($jamaah->getVillage)->name,
                    ]);

                    if (!empty($bagianAlamat)) {
                        $alamatBaru = implode(', ', $bagianAlamat);
                    }
                }
            }

            /**
             * =========================================================
             * PRIORITAS 2: Jika DB tidak dapat, parse dari NIK
             * =========================================================
             */
            if ($alamatBaru === null && $noIdentitas !== '') {
                // bersihkan semua non-digit
                $nik = preg_replace('/\D/', '', $noIdentitas);

                if (strlen($nik) === 16) {
                    try {
                        $result = $reader->read($nik);

                        if ($result && $result->valid) {
                            $bagianAlamat = array_filter([
                                $result->province ?? null,
                                $result->city ?? null,
                                $result->subdistrict ?? null,
                            ]);

                            if (!empty($bagianAlamat)) {
                                $alamatBaru = implode(', ', $bagianAlamat);
                            }
                        }
                    } catch (\Throwable $e) {
                        // abaikan error parsing NIK
                    }
                }
            }

            /**
             * =========================================================
             * Tulis ulang ke kolom L jika ada hasil
             * =========================================================
             */
            if ($alamatBaru !== null) {
                $cellAddress = $colAlamat . $row;
                $sheet->setCellValue($cellAddress, $alamatBaru);

                // Optional: highlight kuning muda
                $sheet->getStyle($cellAddress)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFACD'],
                    ],
                ]);
            }
        }

        // Simpan file output hasil duplicate
        $outputFileName = 'jamaah_updated_' . now()->format('Ymd_His') . '.xlsx';
        $outputPath = storage_path('app/exports/' . $outputFileName);

        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        // Hapus file temp
        Storage::disk('local')->delete($tempPath);

        // Download hasil
        return response()->download($outputPath, $outputFileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }


}
