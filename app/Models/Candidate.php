<?php

namespace App\Models;

use App\Support\BiodataFields;
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
        'biodata_signed_path', 'biodata_signed_at',
        'user_id',
        'job_application_id',
        'name',
        'nickname',
        'gender',
        'birth_place',
        'birth_date',
        'nik',
        'no_kk',
        'blood_type',
        'body_height_weight',
        'religion',
        'nationality',
        'status_marital',
        'marriage_date',
        'mother_name',
        'emergency_contact_name',
        'emergency_contact_phone',
        'no_str',
        'str_seumur_hidup',
        'str_berlaku_mulai',
        'str_berlaku_sampai',
        'str_file_path',
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
        'biodata_confirmed_at',
    ];

    protected $casts = [
        'marriage_date' => 'date',
        'birth_date' => 'date',
        'biodata_confirmed_at' => 'datetime',
        'biodata_signed_at' => 'datetime',
        'str_seumur_hidup' => 'boolean',
        'str_berlaku_mulai' => 'date',
        'str_berlaku_sampai' => 'date',
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

    /** Nama dan NIK mengikuti akun (users.name, users.nik); hanya menulis kalau berbeda. */
    public function syncIdentityFromUser(?User $user): void
    {
        if (! $user) return;

        $this->fill(array_filter(['name' => $user->name, 'nik' => $user->nik], fn ($v) => filled($v)));
        if ($this->isDirty()) {
            $this->save();
        }
    }

    /**
     * Isian utama yang sudah diisi saat melamar (jenis kelamin, tempat & tanggal lahir): diambil dari lamaran,
     * terkunci di form biodata. Hanya yang ada isinya di lamaran.
     */
    public function lockedFields(): array
    {
        $this->loadMissing('jobApplication.answers');
        $map = $this->jobApplication?->answersMap() ?? [];
        $out = [];
        foreach (['gender', 'birth_place', 'birth_date'] as $k) {
            $v = $map[$k] ?? null;
            if ($k === 'gender') $v = $v ? mb_substr((string) $v, 0, 10) : null;
            if ($k === 'birth_date' && $v) {
                try { $v = \Illuminate\Support\Carbon::parse($v)->toDateString(); } catch (\Throwable) { $v = null; }
            }
            if (filled($v)) $out[$k] = $v;
        }

        return $out;
    }

    /** Perusahaan lowongan adalah rumah sakit (companies.is_hospital): hanya RS yang menanyakan STR dan uraian posisi kerja. */
    public function isHospital(): bool
    {
        return (bool) $this->biodataCompany()?->is_hospital;
    }

    /** Perusahaan yang master isian biodatanya berlaku untuk kandidat ini (perusahaan lowongan lamaran terbaru). */
    public function biodataCompany(): ?Company
    {
        $this->loadMissing('jobApplication.jobOpening.company');

        return $this->jobApplication?->jobOpening?->company;
    }

    /**
     * Pembuatan Candidate satu pintu (booted() saat onboarding/passed, dan lazy-create saat biodata terbuka).
     * Sekaligus prefill kolom yang MASIH KOSONG dari data lamaran/offering — tidak pernah menimpa isian kandidat.
     */
    public static function ensureFor(JobApplication $app): self
    {
        $map = $app->answersMap();
        $date = function ($v) {
            try { return $v ? \Illuminate\Support\Carbon::parse($v)->toDateString() : null; } catch (\Throwable) { return null; }
        };

        $prefill = array_filter([
            'gender'     => isset($map['gender']) ? mb_substr((string) $map['gender'], 0, 10) : null,
            'birth_place' => $app->offering_birth_place ?: ($map['birth_place'] ?? null),
            'birth_date'  => $date($app->offering_birth_date ?: ($map['birth_date'] ?? null)),
            'ktp_address' => $app->offering_domicile_address ?: ($map['domicile_address'] ?? null),
            'village'     => $app->offering_village,
            'district'    => $app->offering_district,
            'city'        => $app->offering_city,
            'province'    => $app->offering_province,
        ], fn ($v) => filled($v));

        $candidate = static::firstOrCreate(
            ['user_id' => $app->user_id],
            ['job_application_id' => $app->id, 'name' => $app->user->name, 'nik' => $app->user->nik] + $prefill
        );

        $missing = array_filter($prefill, fn ($v, $k) => blank($candidate->{$k}), ARRAY_FILTER_USE_BOTH);
        if ($missing) {
            $candidate->update($missing);
        }
        // Nama (KTP) dan NIK selalu dari akun pelamar (isian di form dikunci); disalin ke candidates untuk HRIS.
        $candidate->syncIdentityFromUser($app->user);
        if ($candidate->job_application_id !== $app->id) {
            $candidate->update(['job_application_id' => $app->id]);
        }
        // Jenis kelamin, tempat & tanggal lahir selalu dari lamaran (terkunci di form).
        $candidate->setRelation('jobApplication', $app);
        if ($locked = $candidate->lockedFields()) {
            $candidate->forceFill($locked)->save();
        }

        return $candidate;
    }

    /**
     * Data diri yang WAJIB dilengkapi kandidat. Tiap item punya `stage` (`biodata` = diisi sejak wawancara pertama,
     * `onboarding` = BPJS/rekening/dokumen) dan `tab`. Isian yang dimatikan/tidak wajib di master perusahaan
     * (App\Support\BiodataFields) tidak ikut. Isian baru biodata (`new`) hanya ikut kalau $includeNew — default false
     * supaya hasil (dan flag `data_diri_belum_lengkap` ke HRIS) untuk kandidat lama tidak berubah.
     *
     * @return array<string, array{label:string, filled:bool, tab:int, stage:string}>
     */
    public function requiredDataChecklist(bool $includeNew = false): array
    {
        $this->loadMissing(['familyMembers', 'educations', 'documents', 'jobApplication.jobOpening', 'user']);

        $has = fn ($v) => filled(is_string($v) ? trim($v) : $v);
        $docTypeHas = fn (string $needle) => $this->documents
            ->contains(fn ($d) => str_contains(mb_strtolower((string) $d->doc_type), $needle));
        $item = fn (string $label, bool $filled, int $tab, ?string $field = null, string $stage = 'biodata', bool $new = false) => compact('label', 'filled', 'tab', 'field', 'stage', 'new');

        $items = [
            'name'           => $item('Nama sesuai KTP', $has($this->user?->name ?? $this->name), 1, 'name'),
            'nik'            => $item('NIK', $has($this->user?->nik ?: $this->nik), 1, 'nik'),
            'gender'         => $item('Jenis kelamin', $has($this->gender), 1, 'gender', 'biodata', true),
            'birth_place'    => $item('Tempat lahir', $has($this->birth_place), 1, 'birth_place', 'biodata', true),
            'birth_date'     => $item('Tanggal lahir', $has($this->birth_date), 1, 'birth_date', 'biodata', true),
            'no_kk'          => $item('No. Kartu Keluarga', $has($this->no_kk), 1, 'no_kk'),
            'mother_name'    => $item('Nama ibu kandung', $has($this->mother_name), 1, 'mother_name'),
            'status_marital' => $item('Status pernikahan', $has($this->status_marital), 1, 'status_marital'),
            'emergency_contact' => $item('Kontak darurat', $has($this->emergency_contact_name) && $has($this->emergency_contact_phone), 1, 'emergency_contact', 'biodata', true),
        ];

        // STR hanya ditanyakan di rumah sakit; wajib kalau jabatannya nakes (job_openings.is_nakes, diisi dari HRIS).
        if ($this->isHospital() && $this->jobApplication?->jobOpening?->is_nakes) {
            $items['no_str'] = $item('No. STR', $has($this->no_str), 1, 'str');
            $items['str_masa_berlaku'] = $item('Masa berlaku STR', $this->str_seumur_hidup || ($this->str_berlaku_mulai && $this->str_berlaku_sampai), 1, 'str');
        }

        $items += [
            'ktp_address'    => $item('Alamat KTP', $has($this->ktp_address), 2, 'address'),
            'village'        => $item('Kelurahan/Desa', $has($this->village), 2, 'address'),
            'district'       => $item('Kecamatan', $has($this->district), 2, 'address'),
            'city'           => $item('Kota/Kabupaten', $has($this->city), 2, 'address'),
            'province'       => $item('Provinsi', $has($this->province), 2, 'address'),
            'bank_number'    => $item('No. Rekening (BSI)', $has($this->bsi_bank_number), 3, null, 'onboarding'),
            'family'         => $item('Data keluarga (min. 1)', $this->familyMembers->isNotEmpty(), 4),
            'education'      => $item('Riwayat pendidikan (min. 1)', $this->educations->isNotEmpty(), 5),
            // Dokumen KTP/KK tidak diminta lagi di sini: diunggah nanti setelah masuk HRIS.
        ];

        $fields = BiodataFields::flat($this->biodataCompany());

        return array_filter($items, function ($it) use ($fields, $includeNew) {
            if ($it['new'] && ! $includeNew) return false;

            return $it['field'] === null || ($fields[$it['field']]['required'] ?? false);
        });
    }

    /** Semua data wajib (versi lama, tanpa isian baru biodata) sudah dilengkapi. */
    public function requiredDataComplete(): bool
    {
        foreach ($this->requiredDataChecklist() as $item) {
            if (! $item['filled']) {
                return false;
            }
        }

        return true;
    }

    /** Checklist tahap biodata (sejak wawancara pertama), termasuk isian baru biodata. */
    public function biodataChecklist(): array
    {
        return array_filter($this->requiredDataChecklist(true), fn ($i) => $i['stage'] === 'biodata');
    }

    public function biodataComplete(): bool
    {
        return collect($this->biodataChecklist())->every(fn ($i) => $i['filled']);
    }

    /** Tempat/tanggal lahir + alamat lengkap sudah terisi di biodata (offering tidak perlu menanyakannya lagi). */
    public function biodataIdentityFilled(): bool
    {
        foreach (['birth_place', 'birth_date', 'ktp_address', 'village', 'district', 'city', 'province'] as $col) {
            if (blank($this->{$col})) return false;
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
