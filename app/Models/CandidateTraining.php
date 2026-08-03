<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateTraining extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'candidate_id', 'type', 'institution', 'material', 'duration', 'location',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
