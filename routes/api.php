<?php

use App\Http\Controllers\Api\HrisIntegrationController;
use App\Http\Middleware\HrisApiAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(HrisApiAuth::class)->group(function () {
    Route::get('/hris-test', [HrisIntegrationController::class, 'healthCheck']);

    Route::post('/integrations/hris/companies', [HrisIntegrationController::class, 'upsertCompany']);

    Route::post('/integrations/hris/job-openings/applicant-counts', [HrisIntegrationController::class, 'applicantCounts']);
    Route::get('/integrations/hris/job-openings/unlinked', [HrisIntegrationController::class, 'unlinkedJobOpenings']);
    Route::get('/integrations/hris/job-openings/{hrisJobOpeningId}/applications', [HrisIntegrationController::class, 'jobOpeningApplications']);
    Route::put('/integrations/hris/job-openings/{hrisJobOpeningId}', [HrisIntegrationController::class, 'upsertJobOpening']);

    Route::get('/integrations/hris/applications/{jobApplicationId}', [HrisIntegrationController::class, 'showApplication']);
    Route::get('/integrations/hris/candidates/{jobApplicationId}', [HrisIntegrationController::class, 'showCandidate']);
    Route::get('/integrations/hris/candidates/{jobApplicationId}/documents/{documentId}', [HrisIntegrationController::class, 'downloadCandidateDocument']);

    Route::patch('/integrations/hris/applications/{jobApplicationId}/status', [HrisIntegrationController::class, 'updateApplicationStatus']);
    Route::post('/integrations/hris/applications/{jobApplicationId}/interview', [HrisIntegrationController::class, 'scheduleInterview']);
    Route::post('/integrations/hris/applications/{jobApplicationId}/interview-result', [HrisIntegrationController::class, 'submitInterviewResult']);
    Route::post('/integrations/hris/applications/{jobApplicationId}/offering-letter', [HrisIntegrationController::class, 'uploadOfferingLetter']);
    Route::post('/integrations/hris/applications/{jobApplicationId}/offering-letter/advance', [HrisIntegrationController::class, 'advanceFromOffering']);
    Route::get('/integrations/hris/applications/{jobApplicationId}/offering-letter/{type}', [HrisIntegrationController::class, 'downloadOfferingLetter'])->where('type', 'recruiter|signed');
});
