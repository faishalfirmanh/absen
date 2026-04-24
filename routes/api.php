<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
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

Route::post('/login', [AuthController::class, 'login']);
Route::get('/view_login', [AuthController::class, 'viewLogin'])->name('login');
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // ← PINDAHKAN ROUTE INI KE PALING ATAS
    Route::middleware(['attendance'])->group(function () {

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

        Route::post('/izin-absen', [AttendanceController::class, 'storeIzin'])
            ->name('save_izin');
        Route::post('/update-izin', [AttendanceController::class, 'updateApproval'])->name('updateIzin');
    });
});
