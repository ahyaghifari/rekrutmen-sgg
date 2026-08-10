<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'id',
        'company_id',
        'title',
        'company_name',
        'quota',
        'max_applicants',
        'applications_deadline',
        'hris_job_opening_id',
        'is_active',
        'description',
        'requirements',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'quota'                 => 'integer',
        'max_applicants'        => 'integer',
        'applications_deadline' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Scope lowongan yang bisa dilamar pelamar (aktif saja).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
