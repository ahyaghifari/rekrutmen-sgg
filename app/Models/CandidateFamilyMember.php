<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateFamilyMember extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'candidate_id', 'relation', 'gender', 'name',
        'birth_date', 'education', 'job', 'phone',
    ];

    protected $casts = ['birth_date' => 'date'];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
