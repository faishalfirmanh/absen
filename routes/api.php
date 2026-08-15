<?php

use App\Http\Controllers\Api\JamaahVaksinController;
use App\Http\Controllers\Api\WaBootMekariController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Api\WaBootController;
use App\Http\Controllers\WaScrapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LiburController;
use App\Http\Controllers\Api\SyncGeneralPaketUmrohController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// routes/api.php — SEMENTARA, hapus setelah debugging selesai


Route::get('/test-mekari-token', function () {
    $token = env('MEKARI_TOKEN');
    $roomId = '45a574cc-f1f4-4c2a-af1c-a87aa2ad1665';

    $response = Illuminate\Support\Facades\Http::withToken($token)
        ->timeout(15)
        ->post('https://service-chat.qontak.com/api/open/v1/messages/whatsapp/bot', [
            'room_id' => $roomId,
            'type' => 'text',
            'text' => 'Halo, ini tes pakai Bearer Token MEKARI_TOKEN dari route! Jika masuk, bot AI siap live! 🚀',
        ]);

    return response()->json([
        'http_status' => $response->status(),
        'response_body' => $response->json()
    ]);
});

// routes/api.php — SEMENTARA
Route::get('/test-gemini', function () {
    $timings = [];
    $start = microtime(true);

    $t0 = microtime(true);
    $faq = json_decode(file_get_contents(base_path('faq_1.json')), true) ?? [];
    $timings['load_faq_detik'] = round(microtime(true) - $t0, 2);

    $t0 = microtime(true);
    $paketRes = \Illuminate\Support\Facades\Http::timeout(10)->get('https://absennamiroh.alhidayah.id/api/get-paket', ['key' => 'namiroh123#']);
    $paketData = $paketRes->json('data', []);
    $timings['load_paket_detik'] = round(microtime(true) - $t0, 2);
    $timings['jumlah_paket'] = count($paketData);

    $context = "=== FAQ UMUM ===\n" . json_encode($faq, JSON_UNESCAPED_UNICODE) . "\n\n"
        . "=== DATA PAKET ===\n" . json_encode($paketData, JSON_UNESCAPED_UNICODE) . "\n\n";
    $timings['panjang_context_karakter'] = strlen($context);

    // Ganti $systemPrompt di bawah dengan prompt lengkap asli punyamu
    $systemPrompt = "Anda adalah Customer Service AI...\n\n" . $context;

    $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . env('GEMINI_API_KEY');

    $t0 = microtime(true);
    try {
        $aiResponse = \Illuminate\Support\Facades\Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($geminiUrl, [
                'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => 'Info paket murah September 2026']]]],
                'generationConfig' => ['temperature' => 0.3],
            ]);
        $timings['gemini_detik'] = round(microtime(true) - $t0, 2);
        $timings['gemini_status'] = $aiResponse->status();
    } catch (\Throwable $e) {
        $timings['gemini_detik'] = round(microtime(true) - $t0, 2);
        $timings['gemini_error'] = $e->getMessage();
    }

    $timings['total_detik'] = round(microtime(true) - $start, 2);
    return response()->json($timings);
});


Route::post('/error_res_login', [AuthController::class, 'viewLogin'])->name('login');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::post('login', [AuthController::class, 'login'])->name('login_post');
Route::get('kirim', [AttendanceController::class, 'sendPesan']);

Route::post('/upload_v', [JamaahVaksinController::class, 'uploadV'])->name('up_v');

Route::middleware('throttle:5,1')->get('/sync/paket-umroh', SyncGeneralPaketUmrohController::class);


Route::get('get-paket', [ReportController::class, 'listPaket'])->name('listPaketExcel');
Route::post('/webhook/fonnte', [WaBootController::class, 'handleV2']);
Route::post('/webhook/mekari', [WaBootMekariController::class, 'handleMekari']);
Route::post('/chatbot/mekari', [WaBootMekariController::class, 'chatbotApi']);


Route::get('tes-kirim/{target}/{message}', [WaBootController::class, 'tesSend']);


Route::post('absen-no-auth', [AttendanceController::class, 'store'])->name('absen-no-auth');

Route::get('detail-task-wa/{id}', [ReportController::class, 'GetDetailWa'])->name('detail-tast-no-auth');
Route::get('report_bulan_no_auth', [ReportController::class, 'monthlyReport'])->name('report_bulan_noauth');

Route::get('detail-absen/{iduser}', [AttendanceController::class, 'GetDetailAbsenUserId'])->name('detail-absen-user');
Route::get('list-user-wactivity', [WaScrapController::class, 'getUser'])->name('get-user-activity');


Route::post('save_wa_scarap', [WaScrapController::class, 'saveWa'])->name('save_wa_scrap');


Route::get('work_location', [AttendanceController::class, 'getLocation'])->name('getLocation');


Route::post('saveAbsenAdmin', [AttendanceController::class, 'saveAttendance'])->name('save_absen_admin');

Route::post('get_detail_attendance', [AttendanceController::class, 'getDetailTimeAttendance'])->name('filter_detail_attendance');

Route::middleware(['auth:sanctum', 'absen_mid'])->group(function () {


    Route::get('report_bulan', [ReportController::class, 'monthlyReport']);
    Route::get('report_tahun', [ReportController::class, 'yearlyReport']);
    Route::get('get_user', [AuthController::class, 'getMe'])->name('get_me');//changeNewPassword
    Route::post('save_new_password', [AuthController::class, 'changeNewPassword'])->name('changeNewPassword');

    Route::prefix('master-libur')->group(function () {
        Route::post('save', [LiburController::class, 'store'])->name('save_libur');
        Route::post('update/{id}', [LiburController::class, 'update'])->name('update_libur');
        Route::get('all', [LiburController::class, 'index'])->name('get_all_libur');
        Route::get('byId', [LiburController::class, 'show'])->name('get_byID_libur');
    });

    // Route izin dipindah ke atas + pakai leading slash
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('absen');
    Route::get('/getImage/{user_id}/{date?}/{limit?}', [AttendanceController::class, 'getImage'])
        ->name('ambil_gambar')
        ->where([
            'user_id' => '[0-9]+',
            'date' => '[0-9]+',
            'limit' => '[0-9]+|all',
        ])
        ->defaults('limit', 'all');

    Route::get('attendance-history', [AttendanceController::class, 'getAttendanceHistory'])
        ->name('attendance-history');


    Route::get('/all-attendance', [AttendanceController::class, 'getAllAttendance'])
        ->name('all-attendance');
    Route::get('/lastImage', [AttendanceController::class, 'getLastImageByUser'])
        ->name('lastImage');
    Route::get('/findizin', [AttendanceController::class, 'getIzinById'])
        ->name('findizin');
    Route::get('/list_izin', [AttendanceController::class, 'listIzin'])
        ->name('list_izin');
    Route::post('/izin-absen', [AttendanceController::class, 'storeIzin'])
        ->name('save_izin');
    Route::post('/update-izin', [AttendanceController::class, 'updateApproval'])->name('updateIzin');

});
