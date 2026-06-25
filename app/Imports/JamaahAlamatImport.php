<?php

namespace App\Imports;

use App\Models\Alhidayah\DataJamaah;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use ZerosDev\NikReader\Reader;

class JamaahAlamatImport implements ToCollection, WithHeadingRow, WithEvents
{
    /**
     * Menyimpan data hasil proses untuk ditulis ulang ke sheet
     * format: [ rowIndex => alamatBaru ]
     */
    protected array $alamatUpdates = [];

    /**
     * Referensi ke spreadsheet agar bisa ditulis di AfterSheet
     */
    protected $sheet;

    /**
     * Index kolom Alamat (0-based dari heading row, A=1 di Excel)
     * Kita deteksi otomatis dari heading
     */
    protected int $alamatColIndex = 4; // default kolom L (sesuaikan jika beda)

    public function collection(Collection $rows)
    {
        // rows di sini sudah mapped by heading (WithHeadingRow)
        // row index di Excel = $index + 2 (karena row 1 = heading)
        foreach ($rows as $index => $row) {
            $excelRowNumber = $index + 2; // +2: row 1 heading, mulai data row 2

            $namaPaspor = trim($row['nama_paspor'] ?? '');
            $noIdentitas = trim($row['no_identitas'] ?? '');
            $alamatBaru = null;

            // ── PRIORITAS 1: Cari di DataJamaah by nama ──────────────────────
            if (!empty($namaPaspor)) {
                $jamaah = DataJamaah::where('nama_jamaah', $namaPaspor)->first();

                if ($jamaah) {
                    $bagianAlamat = [];

                    // Ambil nama dari relasi, hanya jika tidak null
                    if ($jamaah->location_prov && $prov = $jamaah->getProv) {
                        $bagianAlamat[] = $prov->name ?? null; // sesuaikan nama kolom
                    }
                    if ($jamaah->location_city && $city = $jamaah->getCity) {
                        $bagianAlamat[] = $city->name ?? null;
                    }
                    if ($jamaah->location_disct && $kec = $jamaah->getKec) {
                        $bagianAlamat[] = $kec->name ?? null;
                    }
                    if ($jamaah->location_villages && $vil = $jamaah->getVillage) {
                        $bagianAlamat[] = $vil->name ?? null;
                    }

                    $bagianAlamat = array_filter($bagianAlamat); // buang null/kosong

                    if (!empty($bagianAlamat)) {
                        $alamatBaru = implode(', ', $bagianAlamat);
                    }
                }
            }

            // ── PRIORITAS 2: Parse NIK jika alamat dari DB tidak dapat ───────
            if ($alamatBaru === null && !empty($noIdentitas)) {
                // Pastikan NIK 16 digit (ada yang diawali apostrof di Excel)
                $nik = preg_replace('/\D/', '', $noIdentitas);

                if (strlen($nik) === 16) {
                    try {
                        $reader = new Reader();
                        $result = $reader->read($nik);

                        if ($result->valid) {
                            $bagianAlamat = array_filter([
                                $result->province ?? null,
                                $result->city ?? null,
                                $result->subdistrict ?? null,
                            ]);

                            if (!empty($bagianAlamat)) {
                                $alamatBaru = implode(', ', $bagianAlamat);
                            }
                        }
                    } catch (\Exception $e) {
                        // NIK tidak valid, biarkan alamat tetap seperti semula
                    }
                }
            }

            // Simpan mapping: baris Excel => alamat baru
            if ($alamatBaru !== null) {
                $this->alamatUpdates[$excelRowNumber] = $alamatBaru;
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Deteksi kolom "Alamat" dari heading row 1
                $alamatCol = $this->detectAlamatColumn($sheet);

                foreach ($this->alamatUpdates as $rowNumber => $alamatBaru) {
                    $cellAddress = $alamatCol . $rowNumber;
                    $sheet->setCellValue($cellAddress, $alamatBaru);

                    // Tandai baris yang diupdate dengan warna kuning muda
                    $sheet->getStyle($cellAddress)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFACD'],
                        ],
                    ]);
                }
            },
        ];
    }

    /**
     * Cari kolom dengan heading "Alamat" (case-insensitive)
     */
    protected function detectAlamatColumn($sheet): string
    {
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($colIndex = 1; $colIndex <= $highestColIndex; $colIndex++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $cellValue = strtolower(trim($sheet->getCell($colLetter . '1')->getValue()));

            if ($cellValue === 'alamat') {
                return $colLetter;
            }
        }

        return 'D'; // fallback default
    }
}