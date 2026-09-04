<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'hris_code', 'name', 'logo_path', 'hero_image_path', 'brand_color', 'email', 'phone', 'is_active', 'requires_mcu',
    ];

    protected $casts = ['is_active' => 'boolean', 'requires_mcu' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }

    public function formSections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(CompanyAgreement::class)->orderBy('sort_order');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }
}
