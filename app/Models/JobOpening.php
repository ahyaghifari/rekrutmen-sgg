<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'company_name',
        'quota',
        'is_active',
        'description',
        'requirements',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quota' => 'integer',
    ];

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
