<?php

use App\Http\Controllers\DokumentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

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

Route::get('/login', function () {
    return 111; //view('welcome');
})->name('login');

Route::get('/', function () {
    return 'hallo word'; //view('welcome');
});

Route::get('welcome/check_document', function () {
    return view('vaksin');
});

Route::get('tes', function () {
    return Hash::make('isal123');
});


Route::get('vaksin', [DokumentController::class, 'cetakPdf']);