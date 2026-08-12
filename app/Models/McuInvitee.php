<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McuInvitee extends Model
{
    use HasUlids;

    protected $fillable = ['mcu_schedule_id', 'job_application_id', 'result', 'health_notes'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(McuSchedule::class, 'mcu_schedule_id');
    }
}
