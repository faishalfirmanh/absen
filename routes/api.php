<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\WaBootController;
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


Route::post('/error_res_login', [AuthController::class, 'viewLogin'])->name('login');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::post('login', [AuthController::class, 'login'])->name('login_post');
Route::get('kirim', [AttendanceController::class, 'sendPesan']);


Route::middleware('throttle:5,1')->get('/sync/paket-umroh', SyncGeneralPaketUmrohController::class);


Route::get('get-paket', [ReportController::class, 'listPaket'])->name('listPaketExcel');
Route::post('/webhook/fonnte', [WaBootController::class, 'handle']);


Route::post('absen-no-auth', [AttendanceController::class, 'store'])->name('absen-no-auth');

Route::get('detail-task-wa/{id}', [ReportController::class, 'GetDetailWa'])->name('detail-tast-no-auth');
Route::get('report_bulan_no_auth', [ReportController::class, 'monthlyReport'])->name('report_bulan_noauth');

Route::get('detail-absen/{iduser}', [AttendanceController::class, 'GetDetailAbsenUserId'])->name('detail-absen-user');
Route::get('list-user-wactivity', [WaScrapController::class, 'getUser'])->name('get-user-activity');


Route::post('save_wa_scarap', [WaScrapController::class, 'saveWa'])->name('save_wa_scrap');


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
