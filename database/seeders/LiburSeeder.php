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
            '2026-06-16' => 'tahun baru islam',
            '2026-06-17' => 'ultah man united',
            '2026-06-18' => 'ultah persebaya'
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
