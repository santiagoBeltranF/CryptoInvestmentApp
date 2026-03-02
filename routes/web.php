<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CryptoController;

Route::get('/', [CryptoController::class, 'index']);
Route::get('/api/update', [CryptoController::class, 'updatePrices']);
Route::get('/api/data', [CryptoController::class, 'getData']);