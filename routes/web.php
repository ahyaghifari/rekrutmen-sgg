<?php

use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ProfileController;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $openings = JobOpening::active()->orderBy('created_at', 'desc')->get();
    return view('welcome', compact('openings'));
})->name('welcome');

Route::get('/lowongan/{jobOpening}', function (JobOpening $jobOpening) {
    abort_if(! $jobOpening->is_active, 404);
    return view('lowongan.show', compact('jobOpening'));
})->name('lowongan.show');

Route::get('/dashboard', function () {
    $applications = auth()->user()->jobApplications()->with('jobOpening')->latest()->get();
    $openings = JobOpening::active()->orderBy('created_at', 'desc')->get();
    return view('dashboard', compact('applications', 'openings'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/lamaran', [JobApplicationController::class, 'index'])->name('lamaran.index');
    Route::get('/lamaran/{application}', [JobApplicationController::class, 'show'])->name('lamaran.show');
    Route::get('/lamar/{jobOpening}', [JobApplicationController::class, 'create'])->name('lamaran.create');
    Route::post('/lamar/{jobOpening}', [JobApplicationController::class, 'store'])->name('lamaran.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
