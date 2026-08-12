<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasUlids;

    protected $fillable = ['job_application_id', 'started_at', 'deadline_at', 'submitted_at', 'score', 'status'];

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

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'in_progress' && now()->greaterThan($this->deadline_at);
    }

    /**
     * Nilai jawaban yang disubmit dan simpan hasilnya. $answers: [quiz_question_id => selected_option].
     */
    public function gradeAndSubmit(array $answers): void
    {
        $questions   = QuizQuestion::whereIn('id', array_keys($answers))->get()->keyBy('id');
        $totalPoints = QuizQuestion::where('quiz_id', $this->jobApplication->jobOpening->quiz_id)->sum('points') ?: 1;
        $earned      = 0;

        foreach ($answers as $questionId => $selected) {
            $question  = $questions->get($questionId);
            if (! $question) continue;

            $isCorrect = $selected !== null && $selected === $question->correct_option;
            if ($isCorrect) $earned += $question->points;

            $this->answers()->create([
                'quiz_question_id' => $questionId,
                'selected_option'  => $selected,
                'is_correct'       => $isCorrect,
            ]);
        }

        $this->update([
            'submitted_at' => now(),
            'score'        => round($earned / $totalPoints * 100, 2),
            'status'       => 'submitted',
        ]);
    }
}
