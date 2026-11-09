<?php

namespace App\Models;

use App\Models\Concerns\NotifiesRescheduleDecision;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class McuInvitee extends Model
{
    use HasUlids, NotifiesRescheduleDecision;

    protected $fillable = ['mcu_schedule_id', 'job_application_id', 'status_mcu', 'catatan_mcu', 'response', 'response_reason', 'proposed_date', 'proposed_time', 'responded_at', 'reschedule_status', 'reschedule_decided_by', 'reschedule_decided_at', 'reschedule_note'];

    protected $casts = ['proposed_date' => 'date', 'responded_at' => 'datetime', 'reschedule_decided_at' => 'datetime'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(McuSchedule::class, 'mcu_schedule_id');
    }

    public function nurseAssessment(): HasOne
    {
        return $this->hasOne(McuNurseAssessment::class);
    }

    public function labResult(): HasOne
    {
        return $this->hasOne(McuLabResult::class);
    }

    public function radiologyResult(): HasOne
    {
        return $this->hasOne(McuRadiologyResult::class);
    }

    public function doctorExamination(): HasOne
    {
        return $this->hasOne(McuDoctorExamination::class);
    }

    public function doctorExamDone(): bool
    {
        return $this->doctorExamination()->exists();
    }

    public function hasAnyMcuResult(): bool
    {
        return $this->nurseAssessment()->exists()
            || $this->labResult()->exists()
            || $this->radiologyResult()->exists()
            || $this->doctorExamination()->exists();
    }
}
