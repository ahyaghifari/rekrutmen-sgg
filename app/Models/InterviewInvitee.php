<?php

namespace App\Models;

use App\Models\Concerns\NotifiesRescheduleDecision;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewInvitee extends Model
{
    use HasUlids, NotifiesRescheduleDecision;

    protected $fillable = ['interview_schedule_id', 'job_application_id', 'response', 'response_reason', 'proposed_date', 'proposed_time', 'responded_at', 'reschedule_status', 'reschedule_decided_by', 'reschedule_decided_at', 'reschedule_note'];

    protected $casts = ['proposed_date' => 'date', 'responded_at' => 'datetime', 'reschedule_decided_at' => 'datetime'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InterviewSchedule::class, 'interview_schedule_id');
    }
}
