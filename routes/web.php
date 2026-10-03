<?php

use App\Exports\ApplicationsExport;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\OfferingLetterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\QuizController;
use App\Models\JobOpening;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', [PublicController::class, 'welcome'])->name('welcome');
Route::get('/lowongan/{jobOpening}', [PublicController::class, 'lowongan'])->name('lowongan.show');

Route::get('/dashboard', function () {
    $applications = auth()->user()->jobApplications()
        ->with(['jobOpening.company', 'jobOpening.quizzes', 'quizAttempts', 'interviewInvitee.schedule', 'mcuInvitee.schedule', 'offlineTestInvitees.schedule.offlineTest'])
        ->latest()->get();
    $openings = JobOpening::with('company')->active()
        ->whereHas('company', fn ($q) => $q->where('is_active', true))
        ->orderBy('created_at', 'desc')->get();
    return view('dashboard', compact('applications', 'openings'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/lamaran', [JobApplicationController::class, 'index'])->name('lamaran.index');
    Route::get('/lamaran/{application}', [JobApplicationController::class, 'show'])->name('lamaran.show');
    Route::get('/lamaran/{application}/berkas/{field}', [JobApplicationController::class, 'downloadAttachment'])->name('lamaran.attachment');
    Route::get('/lamar/{jobOpening}', [JobApplicationController::class, 'create'])->name('lamaran.create');
    Route::post('/lamar/{jobOpening}', [JobApplicationController::class, 'store'])->middleware('throttle:10,1')->name('lamaran.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/data-diri-kandidat', [CandidateProfileController::class, 'edit'])->name('data-diri.edit');
    Route::patch('/data-diri-kandidat', [CandidateProfileController::class, 'update'])->name('data-diri.update');
    Route::get('/data-diri-kandidat/dokumen/{document}', [CandidateProfileController::class, 'downloadDocument'])->name('data-diri.dokumen');

    Route::get('/persetujuan', [CandidateProfileController::class, 'agreements'])->name('persetujuan.edit');
    Route::patch('/persetujuan', [CandidateProfileController::class, 'updateAgreements'])->name('persetujuan.update');

    Route::get('/wilayah/{code?}', [\App\Http\Controllers\WilayahController::class, 'show'])
        ->where('code', '[0-9.]*')->middleware('throttle:60,1')->name('wilayah');
    Route::get('/offering-letter/unduh', [OfferingLetterController::class, 'downloadForCandidate'])->name('offering-letter.unduh');
    Route::post('/offering-letter/data', [OfferingLetterController::class, 'updateData'])->middleware('throttle:10,1')->name('offering-letter.data');
    Route::post('/offering-letter/upload-signed', [OfferingLetterController::class, 'uploadSigned'])->middleware('throttle:10,1')->name('offering-letter.upload-signed');
    Route::post('/offering-letter/negosiasi', [OfferingLetterController::class, 'negotiate'])->middleware('throttle:10,1')->name('offering-letter.negosiasi');

    Route::get('/tes-online', [QuizController::class, 'index'])->name('tes-online.show');
    Route::get('/tes-online/{quiz}', [QuizController::class, 'show'])->name('tes-online.detail');
    Route::post('/tes-online/{quiz}/mulai', [QuizController::class, 'start'])->name('tes-online.mulai');
    Route::get('/tes-online/{quiz}/kerjakan', [QuizController::class, 'work'])->name('tes-online.kerjakan');
    Route::post('/tes-online/{quiz}/submit', [QuizController::class, 'submit'])->middleware('throttle:6,1')->name('tes-online.submit');
});

// Admin export — protected by Filament auth guard
Route::get('/kelola-rekrutmen/export-pelamar', function () {
    abort_unless(auth()->guard('web')->check() && auth()->user()->is_staff, 403);
    $id       = request('job_opening_id');
    $status   = request('status') ?: null;
    $opening  = JobOpening::findOrFail($id);

    $user = auth()->user();
    abort_unless(
        $user->hasAnyRole(['super_admin', 'Super Admin']) || $user->company_id === $opening->company_id,
        403
    );

    $suffix   = $status ? '-' . $status : '';
    $filename = 'pelamar-' . \Illuminate\Support\Str::slug($opening->title) . $suffix . '-' . now()->format('Ymd') . '.xlsx';
    return Excel::download(new ApplicationsExport($id, $status), $filename);
})->middleware('throttle:10,1')->name('admin.export.applications');

Route::get('/kelola-rekrutmen/offering-letter/{application}/{type}', [OfferingLetterController::class, 'download'])
    ->middleware(['auth'])
    ->where('type', 'recruiter|signed')
    ->name('admin.offering-letter.download');

require __DIR__.'/auth.php';