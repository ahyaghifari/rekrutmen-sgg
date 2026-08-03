<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSelfAssessment extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'candidate_id', 'strengths', 'weaknesses', 'satisfaction_level', 'reason',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
