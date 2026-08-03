<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEducation extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'candidate_id', 'level', 'school_name', 'major',
        'gpa', 'start_year', 'end_year',
    ];

    protected $casts = [
        'gpa' => 'decimal:2',
        'start_year' => 'integer',
        'end_year' => 'integer',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
