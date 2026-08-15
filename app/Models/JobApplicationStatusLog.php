<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicationStatusLog extends Model
{
    use HasUlids;

    protected $fillable = ['job_application_id', 'from_status', 'to_status', 'changed_by', 'changed_at'];

    protected $casts = ['changed_at' => 'datetime'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
