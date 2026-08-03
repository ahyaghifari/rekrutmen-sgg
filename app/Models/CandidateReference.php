<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateReference extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'candidate_id', 'name', 'address', 'job', 'phone', 'relation',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
