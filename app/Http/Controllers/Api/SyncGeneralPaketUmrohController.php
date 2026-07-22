<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeneralPaketUmrohSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncGeneralPaketUmrohController extends Controller
{
    /**
     * GET /api/sync/paket-umroh?token=xxxxx
     *
     * Dipanggil oleh cron eksternal (cPanel Cron Jobs, cron-job.org, EasyCron, dll)
     * sebagai alternatif kalau server tidak punya akses crontab langsung / Laravel Scheduler.
     */
    public function __invoke(Request $request, GeneralPaketUmrohSyncService $service)
    {
        $token = (string) $request->query('token');
        $expected = (string) config('services.google_sheets.sync_token');

        if ($expected === '' || !hash_equals($expected, $token)) {
            abort(403, 'Token tidak valid');
        }

        $stats = $service->sync();

        Log::info('Sync paket umroh dari Google Sheet selesai (API/cron eksternal)', $stats);

        return response()->json(array_merge(['status' => 'ok'], $stats));
    }
}