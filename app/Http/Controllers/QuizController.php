<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    private function currentApplication()
    {
        $app = Auth::user()->jobApplications()->with('jobOpening.quiz')->latest()->first();
        abort_if(! $app || $app->status !== 'shortlisted' || ! $app->quiz_sent_at, 403, 'Tes online belum tersedia untukmu.');
        abort_if(! $app->jobOpening->quiz, 404, 'Lowongan ini belum punya kuis yang dikonfigurasi.');

        return $app;
    }

    public function show(): View
    {
        $application = $this->currentApplication();
        $attempt = $application->quizAttempt;

        if ($attempt && $attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
        }

        return view('tes-online.show', compact('application', 'attempt'));
    }

    public function start(): RedirectResponse
    {
        $application = $this->currentApplication();

        if (! $application->quizAttempt) {
            $duration = $application->jobOpening->quiz->duration_minutes ?? 60;
            QuizAttempt::create([
                'job_application_id' => $application->id,
                'started_at'         => now(),
                'deadline_at'        => now()->addMinutes($duration),
                'status'             => 'in_progress',
            ]);
        }

        return redirect()->route('tes-online.kerjakan');
    }

    public function work(): View|RedirectResponse
    {
        $application = $this->currentApplication();
        $attempt = $application->quizAttempt;

        abort_if(! $attempt, 404);

        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('tes-online.show');
        }

        $questions = $application->jobOpening->quiz->questions;

        return view('tes-online.work', compact('application', 'attempt', 'questions'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $application = $this->currentApplication();
        $attempt = $application->quizAttempt;

        abort_if(! $attempt, 404);

        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
            return redirect()->route('tes-online.show')->with('warning', 'Waktu pengerjaan sudah habis.');
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('tes-online.show');
        }

        $answers = $request->input('jawaban', []);
        $attempt->gradeAndSubmit($answers);

        return redirect()->route('tes-online.show')->with('success', 'Tes berhasil dikumpulkan.');
    }
}
