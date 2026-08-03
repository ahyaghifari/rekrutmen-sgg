<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Form lamaran pelamar — pengganti Google Form (PRD §4a).
     *
     * Aturan bisnis (PRD §4a): 1 user hanya boleh punya 1 lamaran AKTIF
     * dalam satu waktu. Aktif = status bukan `passed`/`rejected`. Di-enforce
     * di sini (bukan DB constraint) lewat hasActiveApplication() / scopeActive().
     */
    protected $fillable = [
        'user_id',
        'job_opening_id',
        'candidate_id',
        'full_name',
        'gender',
        'whatsapp_number',
        'age',
        'domicile_address',
        'birth_place',
        'birth_date',
        'instagram_handle',
        'linkedin_url',
        'education_level',
        'major',
        'school_name',
        'graduation_year',
        'str_valid_until',
        'other_position_note',
        'work_experience_note',
        'willing_relocate',
        'last_salary',
        'expected_salary',
        'availability',
        'certificate_file_path',
        'cv_file_path',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'str_valid_until' => 'date',
        'age' => 'integer',
        'graduation_year' => 'integer',
        'willing_relocate' => 'boolean',
        'last_salary' => 'decimal:2',
        'expected_salary' => 'decimal:2',
    ];

    /**
     * Konstanta status + daftar status yang dianggap "aktif" (masih berjalan).
     */
    public const STATUS_APPLIED = 'applied';
    public const STATUS_SCREENING = 'screening';
    public const STATUS_INTERVIEW = 'interview';
    public const STATUS_PASSED = 'passed';
    public const STATUS_REJECTED = 'rejected';

    public const ACTIVE_STATUSES = [
        self::STATUS_APPLIED,
        self::STATUS_SCREENING,
        self::STATUS_INTERVIEW,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function offeringLetter(): HasOne
    {
        return $this->hasOne(OfferingLetter::class);
    }

    /**
     * Scope: hanya lamaran yang masih aktif (belum passed/rejected).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Cek apakah seorang user punya lamaran aktif.
     *
     * Dipakai di controller/form untuk enforce business rule
     * "1 user = 1 lamaran aktif" sebelum insert baru.
     */
    public static function hasActiveApplication(int $userId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->active()
            ->exists();
    }
}
