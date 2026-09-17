<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyOfferingBenefit extends Model
{
    use HasUlids;

    protected $fillable = ['company_id', 'label', 'is_starred', 'sort_order'];

    protected $casts = ['is_starred' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
