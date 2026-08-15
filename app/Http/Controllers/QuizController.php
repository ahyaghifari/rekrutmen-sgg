<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    private function currentApplication(): JobApplication
    {
        $app = Auth::user()->jobApplications()->with('jobOpening.quizzes')->latest()->first();
        abort_if(! $app || $app->status !== 'shortlisted' || ! $app->quiz_sent_at, 403, 'Tes online belum tersedia untukmu.');
        abort_if($app->jobOpening->quizzes->isEmpty(), 404, 'Lowongan ini belum punya kuis yang dikonfigurasi.');

        return $app;
    }

    private function ensureQuizBelongsToOpening(JobApplication $application, Quiz $quiz): void
    {
        abort_unless($application->jobOpening->quizzes->contains('id', $quiz->id), 404);
    }

    private function attemptFor(JobApplication $application, Quiz $quiz): ?QuizAttempt
    {
        return QuizAttempt::where('job_application_id', $application->id)->where('quiz_id', $quiz->id)->first();
    }

    /** Daftar semua kuis untuk lowongan yang dilamar, beserta status pengerjaan masing-masing. */
    public function index(): View
    {
        $application = $this->currentApplication();
        $quizzes     = $application->jobOpening->quizzes;

        $attempts = QuizAttempt::where('job_application_id', $application->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->each(fn (QuizAttempt $a) => $a->isExpired() && $a->update(['status' => 'expired']))
            ->keyBy('quiz_id');

        return view('tes-online.index', compact('application', 'quizzes', 'attempts'));
    }

    public function show(Quiz $quiz): View
    {
        $application = $this->currentApplication();
        $this->ensureQuizBelongsToOpening($application, $quiz);

        $attempt = $this->attemptFor($application, $quiz);
        if ($attempt && $attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
        }

        return view('tes-online.show', compact('application', 'quiz', 'attempt'));
    }

    public function start(Quiz $quiz): RedirectResponse
    {
        $application = $this->currentApplication();
        $this->ensureQuizBelongsToOpening($application, $quiz);

        if (! $this->attemptFor($application, $quiz)) {
            $duration = $quiz->duration_minutes ?? 60;
            QuizAttempt::create([
                'job_application_id' => $application->id,
                'quiz_id'            => $quiz->id,
                'started_at'         => now(),
                'deadline_at'        => now()->addMinutes($duration),
                'status'             => 'in_progress',
            ]);
        }

        return redirect()->route('tes-online.kerjakan', $quiz);
    }

    public function work(Quiz $quiz): View|RedirectResponse
    {
        $application = $this->currentApplication();
        $this->ensureQuizBelongsToOpening($application, $quiz);

        $attempt = $this->attemptFor($application, $quiz);
        abort_if(! $attempt, 404);

        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('tes-online.detail', $quiz);
        }

        $questions = $quiz->questions;

        return view('tes-online.work', compact('application', 'quiz', 'attempt', 'questions'));
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        $application = $this->currentApplication();
        $this->ensureQuizBelongsToOpening($application, $quiz);

        $attempt = $this->attemptFor($application, $quiz);
        abort_if(! $attempt, 404);

        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
            return redirect()->route('tes-online.detail', $quiz)->with('warning', 'Waktu pengerjaan sudah habis.');
        }

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('tes-online.detail', $quiz);
        }

        $answers = $request->input('jawaban', []);
        $attempt->gradeAndSubmit($answers);

        return redirect()->route('tes-online.show')->with('success', 'Tes berhasil dikumpulkan.');
    }
}
