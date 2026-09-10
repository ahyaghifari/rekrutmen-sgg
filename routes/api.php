<?php

use App\Http\Controllers\Api\HrisIntegrationController;
use App\Http\Middleware\HrisApiAuth;
use Illuminate\Support\Facades\Route;

// HRIS mengelola seluruh proses rekrutmen langsung ke DB bersama (koneksi `recruitment`).
// Yang tersisa lewat HTTP: pemicu email pemberitahuan pelamar.
Route::middleware(HrisApiAuth::class)->group(function () {
    Route::post('/integrations/hris/notifications', [HrisIntegrationController::class, 'sendNotification']);
    Route::post('/integrations/hris/notifications/flush', [HrisIntegrationController::class, 'flushNotifications']);
});
