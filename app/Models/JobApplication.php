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
        'offering_signed_reviewed_at',
        'offering_signed_revision_reason',
        'onboarding_date',
        'offering_full_name',
        'offering_birth_place',
        'offering_birth_date',
        'offering_domicile_address',
        'offering_village',
        'offering_district',
        'offering_city',
        'offering_province',
        'offering_data_confirmed_at',
    ];

    protected $casts = [
        'quiz_sent_at'                 => 'datetime',
        'psychotest_completed_at'      => 'datetime',
        'offering_letter_uploaded_at'  => 'datetime',
        'offering_letter_signed_at'    => 'datetime',
        'offering_signed_reviewed_at'  => 'datetime',
        'onboarding_date'              => 'date',
        'offering_birth_date'          => 'date',
        'offering_data_confirmed_at'   => 'datetime',
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
                // Alamat sudah diisi kandidat sendiri di form offering letter (offering_village
                // dkk) — tuang langsung ke Candidate saat dibuat, supaya tidak diminta isi ulang
                // di form data diri. Cuma efektif saat CREATE (candidate baru); Candidate lama
                // (rehire) tidak ditimpa di sini.
                $candidate = Candidate::firstOrCreate(
                    ['user_id' => $app->user_id],
                    [
                        'job_application_id' => $app->id,
                        'name' => $app->answersMap()['full_name'] ?? $app->user->name,
                        'ktp_address' => $app->offering_domicile_address,
                        'village' => $app->offering_village,
                        'district' => $app->offering_district,
                        'city' => $app->offering_city,
                        'province' => $app->offering_province,
                    ]
                );

                if ($candidate->job_application_id !== $app->id) {
                    $candidate->update(['job_application_id' => $app->id]);
                }
            }

            if ($app->status === self::STATUS_PASSED) {
                $app->closeJobOpeningIfQuotaFilled();

                // Sudah diterima: lamaran lain milik user yang masih berjalan dihentikan.
                static::where('user_id', $app->user_id)->whereKeyNot($app->id)
                    ->whereNotIn('status', [self::STATUS_PASSED, self::STATUS_REJECTED])
                    ->get()->each->update(['status' => self::STATUS_REJECTED]);
            }
        });
    }

    /** Kuota terisi (jumlah kandidat `passed` >= quota) → nonaktifkan lowongan otomatis. */
    private function closeJobOpeningIfQuotaFilled(): void
    {
        $opening = $this->jobOpening;
        if (! $opening || ! $opening->is_active || $opening->quota <= 0) return;

        $passedCount = self::where('job_opening_id', $opening->id)->where('status', self::STATUS_PASSED)->count();
        if ($passedCount >= $opening->quota) {
            $opening->update(['is_active' => false]);
        }
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
        // Lowongan boleh sudah dihapus (soft delete); lamaran lama tetap harus bisa menampilkan judulnya.
        return $this->belongsTo(JobOpening::class)->withTrashed();
    }

    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    public function offeringLetter(): HasOne
    {
        return $this->hasOne(OfferingLetter::class);
    }

    public function offeringNegotiations(): HasMany
    {
        return $this->hasMany(OfferingNegotiation::class)->orderBy('created_at');
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

    public function offlineTestResults(): HasMany
    {
        return $this->hasMany(OfflineTestResult::class);
    }

    public function offlineTestInvitees(): HasMany
    {
        return $this->hasMany(OfflineTestInvitee::class);
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

    /** Tidak boleh melamar: lowongan ini sudah dilamar (status apa pun), atau sudah diterima (passed) di lowongan mana pun. */
    public static function cannotApplyTo(int|string $userId, int|string $jobOpeningId): bool
    {
        return static::where('user_id', $userId)
            ->where(fn ($q) => $q->where('job_opening_id', $jobOpeningId)->orWhere('status', self::STATUS_PASSED))
            ->exists();
    }

    /**
     * Render PDF offering letter dari template company (offering_letter_template + benefit/
     * ketentuan/penandatangan, diatur di panel rekrutmen) + kompensasi lowongan ini + data
     * kandidat yang sudah dikonfirmasi (offering_full_name dkk). DIOLAH LANGSUNG saat diminta
     * (controller/admin action) — tidak disimpan ke disk/kolom apa pun, jadi selalu pakai
     * data & config TERBARU tanpa perlu "generate ulang" manual. `offering_letter_path` cuma
     * dipakai jalur unggah manual rekruter (override, lihat OfferingLetterController::download).
     */
    /** Alamat offering letter, gabungan dari field terstruktur (village/district/city/province) + detail jalan. */
    public function offeringFullAddress(): string
    {
        return collect([
            $this->offering_domicile_address,
            $this->offering_village ? "Kel. {$this->offering_village}" : null,
            $this->offering_district ? "Kec. {$this->offering_district}" : null,
            $this->offering_city,
            $this->offering_province,
        ])->filter()->implode(', ');
    }

    public function renderOfferingLetterPdf(): \Barryvdh\DomPDF\PDF
    {
        $company = $this->jobOpening->company;

        // Mode tabel (SGG): nominal angka di-auto-convert jadi "Rp X.XXX.XXX" — kolomnya
        // memang selalu diisi nominal. Mode list (RS): tampil apa adanya (bisa range/teks
        // bebas/kosong) — daftarnya sudah dipisah Orientasi/Mitra vs Kontrak di templatenya.
        $isTableMode = $company->offering_letter_template === \App\Enums\OfferingLetterTemplate::Sgg;
        $formatAmount = function (?string $value) use ($isTableMode) {
            if (blank($value)) {
                return null;
            }

            return $isTableMode && is_numeric($value)
                ? 'Rp' . number_format((float) $value, 0, ',', '.')
                : $value;
        };

        $compensationRows = $this->jobOpening->offeringCompensationRows->map(fn ($row) => [
            'label' => $row->label,
            'orientasi' => $formatAmount($row->amount_orientasi),
            'kontrak' => $formatAmount($row->amount_kontrak),
        ])->all();

        $items = $this->jobOpening->offeringCompensationItems->map(fn ($i) => [
            'type' => $i->type,
            'label' => $i->label,
            'amount' => $i->amount,
        ]);

        $backgroundPath = $company->offering_letter_background_path
            ? \Illuminate\Support\Facades\Storage::disk('local')->path($company->offering_letter_background_path)
            : storage_path('app/private/offering-letter-bg/default.jpg');
        $backgroundPath = str_replace('\\', '/', $backgroundPath);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView($company->offering_letter_template->view(), [
            'background_path' => $backgroundPath,
            'full_name' => $this->offering_full_name,
            'birth_place' => $this->offering_birth_place,
            'birth_date' => \Illuminate\Support\Carbon::parse($this->offering_birth_date)->translatedFormat('d F Y'),
            'domicile_address' => $this->offeringFullAddress(),
            'jabatan' => $this->jobOpening->title,
            'lokasi_kerja' => $company->name,
            'tanggal_surat' => now()->translatedFormat('d F Y'),
            'compensation_rows' => $compensationRows,
            'mitra_items' => $items->where('type', 'mitra')->values()->all(),
            'kontrak_items' => $items->where('type', 'kontrak')->values()->all(),
            'benefits' => $company->offeringBenefits->map(fn ($b) => ['label' => $b->label, 'is_starred' => $b->is_starred])->all(),
            'benefit_note' => $company->offering_benefit_note,
            'terms' => $company->offeringTerms->pluck('label')->all(),
            'signatory_name' => $company->offering_signatory_name ?? '-',
            'signatory_title' => $company->offering_signatory_title ?? '-',
            'signatory_signature_path' => $company->offering_signatory_signature_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->path($company->offering_signatory_signature_path)
                : null,
        ]);
    }
}
