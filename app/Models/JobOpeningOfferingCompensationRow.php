<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOpeningOfferingCompensationRow extends Model
{
    use HasUlids;

    protected $fillable = ['job_opening_id', 'label', 'amount_orientasi', 'amount_kontrak', 'sort_order'];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }
}
