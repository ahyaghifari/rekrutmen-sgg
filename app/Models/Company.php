<?php

namespace App\Models;

use App\Enums\OfferingLetterTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'hris_code', 'name', 'logo_path', 'hero_image_path', 'brand_color', 'email', 'phone', 'is_active', 'requires_mcu', 'management_mode',
        'offering_signatory_name', 'offering_signatory_title', 'offering_signatory_signature_path', 'offering_benefit_note', 'offering_letter_template',
        'offering_letter_background_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_mcu' => 'boolean',
        'offering_letter_template' => OfferingLetterTemplate::class,
    ];

    /** Lowongan perusahaan ini dikelola penuh di HRIS (admin rekrutmen read-only). */
    public function isHrisManaged(): bool
    {
        return $this->management_mode === 'hris';
    }

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

    public function offeringBenefits(): HasMany
    {
        return $this->hasMany(CompanyOfferingBenefit::class)->orderBy('sort_order');
    }

    public function offeringTerms(): HasMany
    {
        return $this->hasMany(CompanyOfferingTerm::class)->orderBy('sort_order');
    }

    public function getOfferingSignatureUrlAttribute(): ?string
    {
        return $this->offering_signatory_signature_path ? asset('storage/' . $this->offering_signatory_signature_path) : null;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }
}
