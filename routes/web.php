<?php

use App\Exports\ApplicationsExport;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\QuizController;
use App\Models\JobOpening;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', [PublicController::class, 'welcome'])->name('welcome');
Route::get('/lowongan/{jobOpening}', [PublicController::class, 'lowongan'])->name('lowongan.show');

Route::get('/dashboard', function () {
    $applications = auth()->user()->jobApplications()->with('jobOpening.company')->latest()->get();
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

    Route::get('/tes-online', [QuizController::class, 'show'])->name('tes-online.show');
    Route::post('/tes-online/mulai', [QuizController::class, 'start'])->name('tes-online.mulai');
    Route::get('/tes-online/kerjakan', [QuizController::class, 'work'])->name('tes-online.kerjakan');
    Route::post('/tes-online/submit', [QuizController::class, 'submit'])->middleware('throttle:6,1')->name('tes-online.submit');
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

require __DIR__.'/auth.php';