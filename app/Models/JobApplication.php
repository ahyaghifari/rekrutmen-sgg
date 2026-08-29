<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

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
        'status',
        'quiz_sent_at',
        'quiz_result',
        'psychotest_completed_at',
        'interview_result',
        'offering_letter_path',
        'offering_letter_uploaded_at',
        'offering_letter_signed_path',
        'offering_letter_signed_at',
    ];

    protected $casts = [
        'quiz_sent_at'                 => 'datetime',
        'psychotest_completed_at'      => 'datetime',
        'offering_letter_uploaded_at'  => 'datetime',
        'offering_letter_signed_at'    => 'datetime',
    ];

    /**
     * Konstanta status + daftar status yang dianggap "aktif" (masih berjalan).
     */
    public const STATUS_APPLIED      = 'applied';
    public const STATUS_SCREENING    = 'screening';
    public const STATUS_SHORTLISTED  = 'shortlisted';
    public const STATUS_INTERVIEW    = 'interview';
    public const STATUS_OFFERING     = 'offering';
    public const STATUS_MCU          = 'mcu';
    public const STATUS_ONBOARDING   = 'onboarding';
    public const STATUS_PASSED       = 'passed';
    public const STATUS_REJECTED     = 'rejected';

    public const ACTIVE_STATUSES = [
        self::STATUS_APPLIED,
        self::STATUS_SCREENING,
        self::STATUS_INTERVIEW,
    ];

    /**
     * Setiap perubahan status dicatat otomatis ke job_application_status_logs.
     *
     * Row Candidate dibuat otomatis saat status → onboarding/passed (bukan shortlisted),
     * karena pengisian data kandidat baru relevan di tahap akhir rekrutmen.
     */
    protected static function booted(): void
    {
        static::created(function (JobApplication $app) {
            $app->logStatusChange(null, $app->status);
        });

        static::updated(function (JobApplication $app) {
            if (! $app->wasChanged('status')) return;

            $app->logStatusChange($app->getOriginal('status'), $app->status);

            if (in_array($app->status, [self::STATUS_ONBOARDING, self::STATUS_PASSED])) {
                $candidate = Candidate::firstOrCreate(
                    ['user_id' => $app->user_id],
                    ['job_application_id' => $app->id, 'name' => $app->answersMap()['full_name'] ?? $app->user->name]
                );

                if ($candidate->job_application_id !== $app->id) {
                    $candidate->update(['job_application_id' => $app->id]);
                }
            }
        });
    }

    private function logStatusChange(?string $from, string $to): void
    {
        JobApplicationStatusLog::create([
            'job_application_id' => $this->id,
            'from_status'        => $from,
            'to_status'          => $to,
            'changed_by'         => auth()->id(),
            'changed_at'         => now(),
        ]);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(JobApplicationStatusLog::class)->orderBy('changed_at');
    }

    /**
     * Catat keputusan/milestone yang TIDAK mengubah kolom `status` (mis. hasil tes lulus,
     * yang statusnya tetap `shortlisted`) — supaya tetap muncul di Riwayat Status, bukan cuma
     * perubahan status yang di-log otomatis lewat booted().
     */
    public function logMilestone(string $milestone): void
    {
        $this->logStatusChange($this->status, $milestone);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(JobApplicationAnswer::class);
    }

    /** Ambil semua jawaban sebagai key-value array. */
    public function answersMap(): array
    {
        return $this->answers->pluck('value', 'field_key')->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    public function offeringLetter(): HasOne
    {
        return $this->hasOne(OfferingLetter::class);
    }

    public function interviewInvitee(): HasOne
    {
        return $this->hasOne(InterviewInvitee::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function mcuInvitee(): HasOne
    {
        return $this->hasOne(McuInvitee::class);
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
