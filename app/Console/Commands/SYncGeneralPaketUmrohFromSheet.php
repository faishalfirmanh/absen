<?php

namespace App\Console\Commands;

use App\Services\GeneralPaketUmrohSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SYncGeneralPaketUmrohFromSheet extends Command
{
    /**
     * Jalankan manual: php artisan sheet:sync-paket-umroh
     * Dijadwalkan otomatis lewat Kernel.php (lihat README, Opsi A).
     */
    protected $signature = 'sheet:sync-paket-umroh';

    protected $description = 'Sinkronisasi data paket umroh dari Google Sheet ke database MySQL';

    public function handle(GeneralPaketUmrohSyncService $service)
    {
        $stats = $service->sync();

        $this->info(
            "Selesai. Paket baru: {$stats['parent_baru']}, Paket update: {$stats['parent_update']}, "
            . "Detail hotel: {$stats['detail']}, Dilewati: {$stats['skipped']}"
        );

        Log::info('Sync paket umroh dari Google Sheet selesai (cron scheduler)', $stats);

        return self::SUCCESS;
    }
}