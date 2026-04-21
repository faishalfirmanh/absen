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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

});

Route::middleware(['auth:sanctum', 'attendance'])->group(function () {
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('getImage/{user_id}/{date?}/{limit?}', [AttendanceController::class, 'getImage'])
        ->where([
            'user_id' => '[0-9]+',
            'date' => '[0-9]+',
            'limit' => '[0-9]+|all',
        ])
        ->defaults('limit', 'all');
});