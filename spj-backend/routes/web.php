<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aplikasi ini adalah backend API murni (dikonsumsi oleh frontend terpisah,
| lihat FRONTEND_URL di .env). Route "web" praktis tidak dipakai, kecuali
| untuk halaman info singkat dan health-check bawaan Laravel di /up
| (didaftarkan otomatis lewat bootstrap/app.php).
|
*/

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'message' => 'SPJ backend API aktif. Gunakan prefix /api untuk mengakses endpoint.',
    ]);
});
