<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAdditionalInfo extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'candidate_id', 'allow_reference_check', 'has_disease', 'disease_notes',
        'hobbies', 'languages', 'last_facilities', 'expected_salary_facilities',
        'available_start_date',
    ];

    protected $casts = [
        'allow_reference_check' => 'boolean',
        'has_disease' => 'boolean',
        'available_start_date' => 'date',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
