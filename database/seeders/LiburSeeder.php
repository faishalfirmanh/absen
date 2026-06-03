<?php

namespace Database\Seeders;

use App\Models\Libur;
use Illuminate\Database\Seeder;

class LiburSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // BEST PRACTICE: Jadikan 1 array associative (Tanggal => Keterangan)
        // Ini mencegah error selisih jumlah array dan lebih mudah dibaca
        $data_libur = [
            '2026-05-01' => 'hari buruh',
            '2026-05-14' => 'hari raya waisak',
            '2026-05-27' => 'idul adha',
            '2026-05-31' => 'hari raya waisak',
            '2026-06-01' => 'kesaktian pancasila',
            '2026-06-16' => 'tahun baru islam',
            '2026-08-17' => '17 agustus ',
            '2026-08-25' => 'Maulid nabi  ',
            '2026-12-25' => 'natal ',
        ];

        foreach ($data_libur as $date => $keterangan) {
            // firstOrCreate: Cari berdasarkan date_holiday. 
            // Jika tidak ada, buat baru dengan tambahan kolom keterangan.
            $saved = Libur::firstOrCreate(
                ['date_holiday' => $date], // Parameter 1: Cari berdasarkan ini
                ['keterangan' => $keterangan] // Parameter 2: Jika buat baru, masukkan ini
            );

            if ($saved->wasRecentlyCreated) {
                // wasRecentlyCreated adalah bawaan Laravel untuk mengecek apakah data BENAR-BENAR baru di-insert
                $this->command->info('Berhasil save libur: ' . $saved->date_holiday . " - " . $saved->keterangan);
            } else {
                // Jika data sudah ada sebelumnya di database
                $this->command->comment('Skip, sudah ada: ' . $saved->date_holiday);
            }
        }
    }
}
