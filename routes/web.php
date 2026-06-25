<?php

use App\Http\Controllers\DokumentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ApiJamaahExcelController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/testing', function () {
    return 111; //view('welcome');
})->name('testing');

Route::get('/', function () {
    return 'hallo word'; //view('welcome');
});

Route::get('/jamaah', [ApiJamaahExcelController::class, 'cariJamaah']);
Route::get('/jamaah-view', [ApiJamaahExcelController::class, 'indexAlamat']);
Route::post('/jamaah/proses-alamat', [ApiJamaahExcelController::class, 'proses'])->name('proses-upload');

Route::get('welcome/check_document', function () {
    return view('vaksin');
});

Route::get('/pdf-vaksin', function () {
    return view('sertif_vaksin');
});

Route::get('tes', function () {
    return Hash::make('raiza123');
});


// Route::get('vaksin', [DokumentController::class, 'cetakPdf']);