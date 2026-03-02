<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CryptoController;

Route::get('/', [CryptoController::class, 'index']);
Route::post('/api/add', [CryptoController::class, 'addCrypto']);
Route::get('/api/update', [CryptoController::class, 'updateAll']);
Route::get('/api/data', [CryptoController::class, 'getData']);