<?php

namespace App\Services;

use App\Models\GeneralPaketUmroh;
// Sesuaikan use statement ini kalau model Anda tidak ada di namespace App\Models
// (mis. kalau masih App\HotelDetailPaket di project lama).

class GeneralPaketUmrohSyncService
{
    protected GoogleSheetService $sheet;

    public function __construct(GoogleSheetService $sheet)
    {
        $this->sheet = $sheet;
    }

    /**
     * Tarik data dari Google Sheet lalu sync ke GeneralPaketUmroh (parent) + HotelDetailPaket (child).
     *
     * Aturan baca baris di sheet:
     * - Kalau TANGGAL & NAMA PROGRAM terisi -> baris ini mendefinisikan paket (parent) BARU
     *   (atau paket yang sudah ada, dicari lewat tgl_keberangkatan + nama_program + program_hari).
     * - Kalau TANGGAL & NAMA PROGRAM kosong -> baris ini adalah detail hotel/harga TAMBAHAN
     *   untuk paket (parent) terakhir yang baru saja didefinisikan di atasnya.
     * - Setiap baris (baik yang mendefinisikan parent baru maupun baris lanjutan) SELALU
     *   menghasilkan 1 baris HotelDetailPaket.
     *
     * @return array{parent_baru:int, parent_update:int, detail:int, skipped:int}
     */
    public function sync(): array
    {
        $spreadsheetId = config('services.google_sheets.spreadsheet_id');

        // Nama tab & range diatur lewat .env (GOOGLE_SHEET_RANGE). Range Anda saat ini
        // menghasilkan 17 kolom per baris, dimulai dari Tanggal (tanpa kolom NO).
        $range = config('services.google_sheets.range');

        if (empty($spreadsheetId) || empty($range)) {
            throw new \RuntimeException(
                'GOOGLE_SHEET_ID atau GOOGLE_SHEET_RANGE belum terisi. Cek .env dan '
                . "config/services.php (key 'google_sheets'), lalu jalankan `php artisan config:clear`."
            );
        }

        $rows = $this->sheet->getRows($spreadsheetId, $range);

        $parentBaru = 0;
        $parentUpdate = 0;
        $detail = 0;
        $skipped = 0;

        /** @var GeneralPaketUmroh|null $currentParent */
        $currentParent = null;
        $sudahDibersihkan = []; // id parent yang detail lamanya sudah dihapus di run ini

        foreach ($rows as $row) {
            // Kolom hasil getRows() (tidak menyertakan kolom NO — sudah dikonfirmasi lewat
            // dump data mentah, data langsung mulai dari Tanggal):
            // 1: Tanggal        2: Nama Program   3: Maskapai
            // 4: Rute           5: Program Hari   6: Seat            7: Jamaah (-> detail)
            // 8: Available      9: Miqat Awal     10: Hotel Madinah  11: N (night_madinah)
            // 12: Hotel Makkah  13: N (night_makkah)  14: Harga  15: Triple  16: Double
            // 17: Tambahan Layanan/Fasilitas
            [
                $tglMentah,
                $namaProgram,
                $namaMaskapai,
                $rute,
                $programHariMentah,
                $totalSeatMentah,
                $totalJamaahMentah,
                $availableMentah,
                $miqatAwal,
                $hotelMadinah,
                $nightMadinahMentah,
                $hotelMakkah,
                $nightMakkahMentah,
                $hargaMentah,
                $hargaTripleMentah,
                $hargaDoubleMentah,
                $tambahanLayanan,
            ] = array_pad($row, 17, null);

            //validasi seat dan available
            if (trim((string) $totalSeatMentah) !== '') {
                $lastSeatMentah = $totalSeatMentah;
            } else {
                $totalSeatMentah = $lastSeatMentah;
            }

            if (trim((string) $availableMentah) !== '') {
                $lastAvailableMentah = $availableMentah;
            } else {
                $availableMentah = $lastAvailableMentah;
            }

            $tglKeberangkatan = $this->parseTanggal($tglMentah);
            $namaProgram = trim((string) strtoupper($namaProgram));

            $barisParentBaru = !empty($tglKeberangkatan) && $namaProgram !== '';

            if ($barisParentBaru) {
                $match = [
                    'tgl_keberangkatan' => $tglKeberangkatan,
                    'nama_program' => $namaProgram,
                    'program_hari' => $this->parseInt($programHariMentah),
                ];

                $data = [
                    'nama_maskapai' => $namaMaskapai ?: null,
                    'rute' => $rute ?: null,
                    'total_seat' => $this->parseInt($totalSeatMentah),
                    'available' => $this->parseInt($availableMentah),
                ];

                $existing = GeneralPaketUmroh::where($match)->first();

                if ($existing) {
                    $existing->update($data);
                    $currentParent = $existing;
                    $parentUpdate++;
                } else {
                    $currentParent = GeneralPaketUmroh::create(array_merge($match, $data));
                    $parentBaru++;
                }

                // Hapus detail hotel lama punya parent ini (sekali saja per parent per run),
                // supaya hasil akhir persis mencerminkan sheet saat ini, tidak menumpuk duplikat
                // dari sync sebelumnya.
                if (!isset($sudahDibersihkan[$currentParent->id])) {
                    $currentParent->detailsHotels()->delete();
                    $sudahDibersihkan[$currentParent->id] = true;
                }
            }

            if (!$currentParent) {
                // Baris lanjutan (kosong) tapi belum ada parent sama sekali sebelumnya di sheet ini
                $skipped++;
                continue;
            }

            $adaDataDetail = $hotelMadinah || $hotelMakkah || $hargaMentah || $totalJamaahMentah;

            if (!$adaDataDetail) {
                $skipped++;
                continue; // baris kosong total, lewati
            }

            $currentParent->detailsHotels()->create([
                'total_jamaah' => $this->parseInt($totalJamaahMentah),
                'miqat_awal' => $miqatAwal ?: null,
                'hotel_madinah' => $hotelMadinah ?: null,
                'night_madinah' => $this->parseInt($nightMadinahMentah),
                'hotel_makkah' => $hotelMakkah ?: null,
                'night_makkah' => $this->parseInt($nightMakkahMentah),
                'harga' => $this->parseAngka($hargaMentah),
                'harga_triple' => $this->parseAngka($hargaTripleMentah),
                'harga_double' => $this->parseAngka($hargaDoubleMentah),
                'tambahan_layanan_fasilitas' => $tambahanLayanan ?: null,
            ]);

            $detail++;
        }

        return [
            'parent_baru' => $parentBaru,
            'parent_update' => $parentUpdate,
            'detail' => $detail,
            'skipped' => $skipped,
        ];
    }

    /**
     * Parse tanggal dari sheet. Menangani format "22-Jul-26" (d-M-y, seperti di contoh Anda)
     * lewat fallback strtotime(), maupun format d/m/Y kalau suatu saat formatnya diganti.
     */
    private function parseTanggal($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('d/m/Y', $value);

        if ($date !== false) {
            return $date->format('Y-m-d');
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function parseInt($value): int
    {
        $value = preg_replace('/[^0-9\-]/', '', (string) $value);

        return $value === '' ? 0 : (int) $value;
    }

    /**
     * Parse angka harga. Menangani "39,000,000" (koma=ribuan, sesuai contoh sheet Anda),
     * "39.000.000" (titik=ribuan), "39.000.000,50" / "39,000,000.50" (dengan desimal),
     * maupun angka polos "39000000".
     */
    private function parseAngka($value): float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $value = preg_replace('/[^0-9,.\-]/', '', $value);

        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        if ($commaCount > 1 && $dotCount === 0) {
            // 39,000,000
            $value = str_replace(',', '', $value);
        } elseif ($dotCount > 1 && $commaCount === 0) {
            // 39.000.000
            $value = str_replace('.', '', $value);
        } elseif ($commaCount === 1 && $dotCount > 1) {
            // 39.000.000,50
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($dotCount === 1 && $commaCount > 1) {
            // 39,000,000.50
            $value = str_replace(',', '', $value);
        } elseif ($commaCount === 1 && $dotCount === 0) {
            // 39,5 -> anggap desimal
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}