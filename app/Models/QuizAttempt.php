<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasUlids;

    protected $fillable = ['job_application_id', 'quiz_id', 'started_at', 'deadline_at', 'submitted_at', 'score', 'status'];

    protected $casts = [
        'started_at'   => 'datetime',
        'deadline_at'  => 'datetime',
        'submitted_at' => 'datetime',
        'score'        => 'decimal:2',
    ];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'in_progress' && now()->greaterThan($this->deadline_at);
    }

    /** Lama pengerjaan dari mulai sampai dikumpulkan, mis. "12 menit 34 detik". */
    public function durationLabel(): ?string
    {
        if (! $this->started_at || ! $this->submitted_at) return null;

        $seconds = $this->started_at->diffInSeconds($this->submitted_at);
        $minutes = intdiv($seconds, 60);
        $rest    = $seconds % 60;

        return $minutes > 0 ? "{$minutes} menit {$rest} detik" : "{$rest} detik";
    }

    /**
     * Nilai jawaban yang disubmit dan simpan hasilnya. $answers: [quiz_question_id => selected_option].
     */
    public function gradeAndSubmit(array $answers): void
    {
        $questions      = QuizQuestion::whereIn('id', array_keys($answers))->get()->keyBy('id');
        $totalQuestions = QuizQuestion::where('quiz_id', $this->quiz_id)->count() ?: 1;
        $correctCount   = 0;

        foreach ($answers as $questionId => $selected) {
            $question  = $questions->get($questionId);
            if (! $question) continue;

            $isCorrect = $selected !== null && $selected === $question->correct_option;
            if ($isCorrect) $correctCount++;

            $this->answers()->create([
                'quiz_question_id' => $questionId,
                'selected_option'  => $selected,
                'is_correct'       => $isCorrect,
            ]);
        }

        $this->update([
            'submitted_at' => now(),
            'score'        => round($correctCount / $totalQuestions * 100, 2),
            'status'       => 'submitted',
        ]);
    }
}
