<?php

use App\Http\Controllers\Api\HrisIntegrationController;
use App\Http\Middleware\HrisApiAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(HrisApiAuth::class)->group(function () {
    Route::get('/hris-test', [HrisIntegrationController::class, 'healthCheck']);

    Route::post('/integrations/hris/job-openings/applicant-counts', [HrisIntegrationController::class, 'applicantCounts']);
    Route::put('/integrations/hris/job-openings/{hrisJobOpeningId}', [HrisIntegrationController::class, 'upsertJobOpening']);
});
