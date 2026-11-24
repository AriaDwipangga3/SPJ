<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\BackupSettingController;
use App\Http\Controllers\Api\BerkasController;
use App\Http\Controllers\Api\DokumenWajibController;
use App\Http\Controllers\Api\JenisSpjController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResponsibleController;
use App\Http\Controllers\Api\SignatureController;
use App\Http\Controllers\Api\SpjController;
use App\Http\Controllers\Api\SpjDokumenController;
use App\Http\Controllers\Api\SpjVerificationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// =====================================================================
// BROADCASTING AUTH — untuk private channel (notifikasi user)
// Endpoint ini dipakai Echo untuk minta otorisasi akses private channel
// =====================================================================
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

// =====================================================================
// BERKAS (dokumen & tanda tangan) — URL bertanda tangan, berumur pendek
// Tidak pakai token Sanctum karena dibuka lewat <img>/<a>; keamanannya dari
// middleware `signed` (URL dibuat server untuk user yang berhak, kedaluwarsa 60 menit).
// =====================================================================
Route::middleware('signed')->group(function () {
    Route::get('berkas/dokumen/{dokumen}', [BerkasController::class, 'dokumen'])->name('berkas.dokumen');
    Route::get('berkas/ttd/{spj}/{pihak}', [BerkasController::class, 'ttd'])
        ->whereIn('pihak', ['1', '2'])
        ->name('berkas.ttd');
});

// =====================================================================
// AUTH — PUBLIC
// =====================================================================
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:forgot-password');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

// =====================================================================
// AUTH — PROTECTED (wajib login pakai token Sanctum)
// =====================================================================
Route::middleware('auth:sanctum')->group(function () {

    // ---------- AUTENTIKASI & PROFIL ----------
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::post('/me/change-password', [ProfileController::class, 'changePassword']);

    // ---------- NOTIFIKASI ----------
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    // clear-read HARUS di atas {id}, kalau tidak "clear-read" dianggap sebagai ID notifikasi.
    Route::delete('notifications/clear-read', [NotificationController::class, 'clearRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

    // ---------- MASTER DATA ----------
    Route::get('jenis-spj', [JenisSpjController::class, 'index']);
    Route::get('jenis-spj/{jenis_spj}', [JenisSpjController::class, 'show']);
    Route::get('jenis-spj/{jenis_spj}/dokumen-wajib', [DokumenWajibController::class, 'index']);

    // ---------- RESPONSIBLE ----------
    Route::apiResource('responsibles', ResponsibleController::class)->except(['show']);

    // =================================================================
    // ADMIN-ONLY
    // =================================================================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class)->except(['show']);
        Route::apiResource('jenis-spj', JenisSpjController::class)->except(['index', 'show']);
        Route::apiResource('jenis-spj.dokumen-wajib', DokumenWajibController::class)
            ->shallow()->except(['index', 'show']);

        Route::post('spj/{spj}/verify', [SpjVerificationController::class, 'approve']);
        Route::post('spj/{spj}/reject', [SpjVerificationController::class, 'reject']);

        Route::get('backups', [BackupController::class, 'index']);
        Route::post('backups/run', [BackupController::class, 'run']);
        Route::get('backups/{filename}/download', [BackupController::class, 'download']);
        Route::delete('backups/{filename}', [BackupController::class, 'destroy']);

        Route::get('settings/backup', [BackupSettingController::class, 'show']);
        Route::put('settings/backup', [BackupSettingController::class, 'update']);

        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/summary', [ActivityLogController::class, 'summary']);
        Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show']);
        Route::delete('activity-logs/cleanup', [ActivityLogController::class, 'cleanup']);
    });

    // ---------- SPJ INTI ----------
    Route::get('spj/next-number', [SpjController::class, 'nextNumber']);
    Route::get('spj/export', [SpjController::class, 'export']);
    Route::get('spj/stats', [SpjController::class, 'stats']);
    Route::get('spj/{spj}/pdf', [SpjController::class, 'exportPdf']);
    Route::apiResource('spj', SpjController::class);

    Route::post('spj/{spj}/dokumen/{dokumen}', [SpjDokumenController::class, 'store']);
    Route::delete('spj/{spj}/dokumen/{dokumen}', [SpjDokumenController::class, 'destroy']);

    Route::post('spj/{spj}/tanda-tangan/{pihak}', [SignatureController::class, 'store'])
        ->whereIn('pihak', ['1', '2']);
});