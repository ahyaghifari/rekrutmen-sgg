<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'job_application_id',
        'name',
        'nickname',
        'nik',
        'no_kk',
        'blood_type',
        'body_height_weight',
        'religion',
        'nationality',
        'status_marital',
        'marriage_date',
        'mother_name',
        'ktp_address',
        'village',
        'district',
        'city',
        'province',
        'social_media_accounts',
        'no_bpjskes',
        'faskes',
        'no_bpjstk',
        'company_bpjstk',
        'bsi_bank_number',
        'profile_picture',
        'overall_status',
        'is_onboarded',
    ];

    protected $casts = [
        'marriage_date' => 'date',
        'is_onboarded' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(CandidateFamilyMember::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CandidateEducation::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(CandidateTraining::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(CandidateOrganization::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(CandidateWorkExperience::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(CandidateReference::class);
    }

    public function additionalInfo(): HasOne
    {
        return $this->hasOne(CandidateAdditionalInfo::class);
    }

    public function selfAssessment(): HasOne
    {
        return $this->hasOne(CandidateSelfAssessment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(CandidateAgreement::class);
    }

    /**
     * Data diri yang WAJIB dilengkapi kandidat sebelum onboarding.
     *
     * @return array<string, array{label:string, filled:bool, tab:int}>
     */
    public function requiredDataChecklist(): array
    {
        $this->loadMissing(['familyMembers', 'educations', 'documents']);

        $has = fn ($v) => filled(is_string($v) ? trim($v) : $v);
        $docTypeHas = fn (string $needle) => $this->documents
            ->contains(fn ($d) => str_contains(mb_strtolower((string) $d->doc_type), $needle));

        return [
            'name'           => ['label' => 'Nama sesuai KTP',           'filled' => $has($this->name),           'tab' => 1],
            'nik'            => ['label' => 'NIK',                       'filled' => $has($this->nik),            'tab' => 1],
            'no_kk'          => ['label' => 'No. Kartu Keluarga',       'filled' => $has($this->no_kk),          'tab' => 1],
            'mother_name'    => ['label' => 'Nama ibu kandung',         'filled' => $has($this->mother_name),    'tab' => 1],
            'status_marital' => ['label' => 'Status pernikahan',        'filled' => $has($this->status_marital), 'tab' => 1],
            'ktp_address'    => ['label' => 'Alamat KTP',               'filled' => $has($this->ktp_address),    'tab' => 2],
            'village'        => ['label' => 'Kelurahan/Desa',           'filled' => $has($this->village),        'tab' => 2],
            'district'       => ['label' => 'Kecamatan',                'filled' => $has($this->district),       'tab' => 2],
            'city'           => ['label' => 'Kota/Kabupaten',           'filled' => $has($this->city),           'tab' => 2],
            'province'       => ['label' => 'Provinsi',                 'filled' => $has($this->province),       'tab' => 2],
            'bank_number'    => ['label' => 'No. Rekening (BSI)',       'filled' => $has($this->bsi_bank_number), 'tab' => 3],
            'family'         => ['label' => 'Data keluarga (min. 1)',   'filled' => $this->familyMembers->isNotEmpty(), 'tab' => 4],
            'education'      => ['label' => 'Riwayat pendidikan (min. 1)', 'filled' => $this->educations->isNotEmpty(), 'tab' => 5],
            'doc_ktp'        => ['label' => 'Dokumen KTP',              'filled' => $docTypeHas('ktp'),          'tab' => 12],
            'doc_kk'         => ['label' => 'Dokumen Kartu Keluarga',   'filled' => $docTypeHas('kk') || $docTypeHas('kartu keluarga'), 'tab' => 12],
        ];
    }

    /** Semua data wajib sudah dilengkapi. */
    public function requiredDataComplete(): bool
    {
        foreach ($this->requiredDataChecklist() as $item) {
            if (! $item['filled']) {
                return false;
            }
        }

        return true;
    }

    /** Masih ada persetujuan efektif yang belum disetujui kandidat ini. */
    public function agreementsPending(): bool
    {
        $this->loadMissing([
            'agreements',
            'jobApplication.jobOpening.company.agreements', 'jobApplication.jobOpening.agreements',
        ]);

        $effective = $this->jobApplication?->jobOpening?->effectiveAgreements() ?? collect();
        $approved = $this->agreements
            ->filter(fn ($a) => $a->company_agreement_id || $a->job_opening_agreement_id)
            ->count();

        return $approved < $effective->count();
    }
}
