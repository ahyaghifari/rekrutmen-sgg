<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Company;
use Illuminate\Support\Carbon;

/**
 * Data untuk cetak biodata (PDF): meniru form `Form Biodata Pelamar.docx` (tata letak di
 * resources/views/candidate-profile/biodata-pdf.blade.php), isian tunggal mengikuti master perusahaan (BiodataFields).
 */
class BiodataData
{
    public const SATISFACTION = [
        'very_unsatisfied' => 'Sangat tidak puas', 'unsatisfied' => 'Tidak puas', 'less_satisfied' => 'Agak tidak puas',
        'satisfied' => 'Cukup puas', 'very_satisfied' => 'Sangat puas',
    ];

    private const FAMILY_SLOTS = ['Ayah', 'Ibu', 'Saudara 1', 'Saudara 2', 'Saudara 3', 'Saudara 4', 'Saudara 5', 'Pasangan', 'Anak 1', 'Anak 2', 'Anak 3'];

    /** Urutan baris data pribadi pada form docx (kunci yang tidak ada dilewati). */
    private const PRINT_ORDER = [
        'name', 'gender', 'nickname', 'blood_type', 'birth_place', 'body_height_weight', 'status_marital', 'marriage_date',
        'email', 'phone', 'social_media_accounts', 'emergency_contact', 'last_education', 'nik', 'no_kk', 'mother_name', 'address',
        'nationality', 'religion', 'str',
    ];

    /** Label seperti pada form docx. */
    private const PRINT_LABELS = [
        'name' => 'Nama Lengkap (Sesuai KTP)', 'birth_place' => 'Tempat, Tanggal Lahir', 'body_height_weight' => 'TB & BB',
        'marriage_date' => 'Tanggal Pernikahan', 'emergency_contact' => 'Nama & Nomor Darurat', 'nik' => 'No KTP',
        'address' => 'Alamat / Tempat Tinggal', 'nationality' => 'Kebangsaan', 'str' => 'STR (Khusus Nakes)',
        'blood_type' => 'Gol. Darah', 'phone' => 'No HP', 'religion' => 'Agama', 'nickname' => 'Nama Panggilan',
    ];

    public static function for(Candidate $candidate): array
    {
        $candidate->load([
            'user', 'familyMembers', 'educations', 'trainings', 'organizations', 'workExperiences',
            'references', 'additionalInfo', 'selfAssessment', 'jobApplication.jobOpening.company', 'jobApplication.answers',
        ]);
        $company = $candidate->biodataCompany();
        $info = $candidate->additionalInfo;
        $assess = $candidate->selfAssessment;

        return [
            'company' => $company?->name ?? 'Perusahaan',
            'hospital' => $candidate->isHospital(),
            'city' => config('biodata.signing_city'),
            'background' => $company?->offeringBackgroundFile() ?? Company::defaultOfferingBackgroundFile(),
            'date' => now()->translatedFormat('j F Y'),
            'name' => $candidate->user?->name ?? $candidate->name,
            'personal' => self::personal($candidate, $company),
            // Nilai mentah untuk isian bergaya pilihan (L/P, status pernikahan, STR aktif/tidak aktif).
            'raw' => [
                'gender' => $candidate->gender,
                'status' => $candidate->status_marital,
                'str_active' => $candidate->str_seumur_hidup || ($candidate->str_berlaku_sampai && $candidate->str_berlaku_sampai->isFuture()),
                'str_until' => $candidate->str_seumur_hidup ? 'seumur hidup' : $candidate->str_berlaku_sampai?->translatedFormat('j M Y'),
            ],
            'family' => self::family($candidate),
            'educations' => self::educations($candidate),
            'trainings' => $candidate->trainings->map(fn ($t) => [$t->type, $t->institution, $t->material, $t->duration, $t->location])->all(),
            'organizations' => $candidate->organizations->map(fn ($o) => [$o->organization_name, $o->field, $o->position, $o->duration, $o->location])->all(),
            'works' => self::works($candidate),
            'references' => $candidate->references->map(fn ($r) => [$r->name, $r->address, $r->job, $r->phone, $r->relation])->all(),
            'info' => [
                'allow_reference_check' => $info?->allow_reference_check, // null = belum diisi
                'has_disease' => $info?->has_disease,
                'disease_notes' => $info?->disease_notes,
                'hobbies' => $info?->hobbies,
                'languages' => $info?->languages,
                'last_facilities' => $info?->last_facilities,
                'expected' => $info?->expected_salary_facilities,
                'available_start' => $info?->available_start_date?->translatedFormat('j F Y'),
            ],
            'assessment' => [
                'strengths' => $assess?->strengths,
                'weaknesses' => $assess?->weaknesses,
                'satisfaction' => $assess?->satisfaction_level,
                'reason' => $assess?->reason,
            ],
        ];
    }

    /** Baris data pribadi [label, nilai, kunci, ditempatkan_master, anchor], urut seperti form docx. */
    private static function personal(Candidate $c, $company): array
    {
        $rows = [];
        $fields = BiodataFields::sections($company);
        // Email, No HP, Pendidikan Terakhir mengikuti urutan docx: sesudah Tanggal Menikah (atau Status Pernikahan kalau dimatikan).
        $extrasAfter = isset(BiodataFields::flat($company)['marriage_date']) ? 'marriage_date' : 'status_marital';
        foreach (['identitas', 'alamat', 'informasi_tambahan', 'penilaian_diri'] as $section) {
            foreach ($fields[$section] ?? [] as $f) {
                $k = $f['key'];
                $value = match (true) {
                    $k === 'name' => $c->user?->name ?? $c->name, // dari akun
                    $k === 'nik' => $c->user?->nik ?? $c->nik,
                    $k === 'birth_date' => null, // digabung ke "Tempat, Tanggal Lahir"
                    $k === 'birth_place' => trim(($c->birth_place ?? '') . ($c->birth_date ? ', ' . $c->birth_date->translatedFormat('j F Y') : ''), ', '),
                    $f['type'] === 'date' => $c->{$k}?->translatedFormat('j F Y'),
                    $f['type'] === 'pair' => trim(($c->{$f['def']['columns'][0]} ?? '') . ' / ' . ($c->{$f['def']['columns'][1]} ?? ''), ' /'),
                    $f['type'] === 'str' => self::str($c),
                    $f['type'] === 'address' => self::address($c),
                    default => $c->{$k},
                };
                if ($k === 'birth_date' || ($f['type'] === 'str' && $value === null)) continue;
                $rows[] = [$f['label'], $value, $k, $f['block'], $f['after'] ?? null];

                if ($k === $extrasAfter) {
                    // Email, No HP dan pendidikan terakhir tidak diinput ulang: dari akun dan riwayat pendidikan.
                    $rows[] = ['Email', $c->user?->email, 'email', false, null];
                    // No HP dari data utama: akun, atau nomor WA yang diisi di form lamaran (tidak ditanyakan ulang di biodata).
                    $rows[] = ['No HP', $c->user?->phone ?: ($c->jobApplication?->answersMap()['whatsapp_number'] ?? null), 'phone', false, null];
                    $rows[] = ['Pendidikan Terakhir', self::lastEducation($c), 'last_education', false, null];
                }
            }
        }

        return self::arrange($rows);
    }

    /**
     * Susun baris cetak: isian dasar mengikuti urutan form docx; isian yang ditempatkan master (atau tidak ada di daftar docx,
     * mis. media sosial) disisipkan tepat di bawah isian yang ditempeli, atau di akhir kalau isian itu tidak ada.
     *
     * @param list<array> $rows [label, value, key, block, after]
     */
    private static function arrange(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $r) $byKey[$r[2]] = $r;

        $ordered = [];
        foreach (self::PRINT_ORDER as $k) {
            if (isset($byKey[$k]) && ! $byKey[$k][3]) $ordered[] = $k;
        }
        foreach ($byKey as $k => $r) {
            if (in_array($k, $ordered, true)) continue;
            $at = $r[4] ? array_search($r[4], $ordered, true) : false;
            $at === false ? $ordered[] = $k : array_splice($ordered, $at + 1, 0, [$k]);
        }

        return array_map(function ($k) use ($byKey) {
            $r = $byKey[$k];
            $r[0] = self::PRINT_LABELS[$k] ?? $r[0];

            return $r;
        }, $ordered);
    }

    private static function str(Candidate $c): ?string
    {
        if (! $c->isHospital()) return null; // STR hanya untuk rumah sakit

        return $c->no_str ?: '—';
    }

    private static function address(Candidate $c): string
    {
        return collect([$c->ktp_address, $c->village ? "Kel. {$c->village}" : null, $c->district ? "Kec. {$c->district}" : null, $c->city, $c->province])
            ->filter()->implode(', ');
    }

    private static function lastEducation(Candidate $c): ?string
    {
        // Pendidikan terakhir diisi saat melamar (form lamaran), itu yang dipakai.
        $c->loadMissing('jobApplication.answers');
        if (filled($lvl = $c->jobApplication?->answersMap()['education_level'] ?? null)) {
            return $lvl;
        }

        $e = $c->educations->sortByDesc(fn ($e) => [self::rank($e->level), (int) $e->end_year])->first();

        return $e ? trim($e->level . ($e->school_name ? ' — ' . $e->school_name : '')) : null;
    }

    private static function rank(?string $level): int
    {
        return match (true) {
            (bool) preg_match('/S-?3|doktor/i', (string) $level) => 7,
            (bool) preg_match('/S-?2|magister/i', (string) $level) => 6,
            (bool) preg_match('/S-?1|sarjana|D-?4/i', (string) $level) => 5,
            (bool) preg_match('/D-?3/i', (string) $level) => 4,
            (bool) preg_match('/D-?[12]/i', (string) $level) => 3,
            (bool) preg_match('/SMA|SMK|MA/i', (string) $level) => 2,
            default => 1,
        };
    }

    /**
     * Pendidikan formal ke baris tetap form: SMA/SMK, Diploma, Strata I, Lainnya. Baris tanpa data tetap dicetak kosong;
     * jenjang di luar tiga baris pertama masuk "Lainnya" dengan nama jenjangnya.
     *
     * @return list<array{0:string,1:?string,2:?string,3:mixed,4:mixed,5:mixed}> [tingkat, sekolah, jurusan, ipk, dari, sampai]
     */
    private static function educations(Candidate $c): array
    {
        $slots = ['SMA / SMK' => [], 'Diploma – DI / DII / DIII' => [], 'Strata I – SI' => [], 'Lainnya' => []];
        foreach ($c->educations as $e) {
            $slot = match (true) {
                (bool) preg_match('/SMA|SMK|\bMA\b/i', (string) $e->level) => 'SMA / SMK',
                (bool) preg_match('/\bD-?[123]\b/i', (string) $e->level) => 'Diploma – DI / DII / DIII',
                (bool) preg_match('/\bS-?1\b|sarjana/i', (string) $e->level) => 'Strata I – SI',
                default => 'Lainnya',
            };
            $slots[$slot][] = $e;
        }

        $rows = [];
        foreach ($slots as $label => $list) {
            foreach ($list ?: [null] as $i => $e) {
                $rows[] = [
                    $e && $label === 'Lainnya' ? 'Lainnya: ' . $e->level : ($i === 0 || ! $e ? $label : ''),
                    $e?->school_name, $e?->major, $e?->gpa, $e?->start_year, $e?->end_year,
                ];
            }
        }

        return $rows;
    }

    /** Slot tetap sesuai form (Ayah, Ibu, Saudara 1–5, Pasangan, Anak 1–3); relasi lain ditambahkan di bawahnya. */
    private static function family(Candidate $c): array
    {
        $slots = array_fill_keys(self::FAMILY_SLOTS, null);
        $extra = [];
        $count = ['saudara' => 0, 'anak' => 0];

        foreach ($c->familyMembers as $m) {
            $rel = mb_strtolower(trim((string) $m->relation));
            $slot = match (true) {
                $rel === 'ayah' || $rel === 'bapak' => 'Ayah',
                $rel === 'ibu' || $rel === 'mama' => 'Ibu',
                in_array($rel, ['pasangan', 'suami', 'istri'], true) => 'Pasangan',
                str_starts_with($rel, 'saudara') || in_array($rel, ['kakak', 'adik'], true) => ++$count['saudara'] <= 5 ? 'Saudara ' . $count['saudara'] : null,
                str_starts_with($rel, 'anak') => ++$count['anak'] <= 3 ? 'Anak ' . $count['anak'] : null,
                default => null,
            };
            if ($slot && $slots[$slot] === null) {
                $slots[$slot] = $m;
            } else {
                $extra[] = $m;
            }
        }

        $row = fn ($label, $m) => [$label, $m?->gender ? mb_substr($m->gender, 0, 1) : null, $m?->name, $m?->birth_date?->translatedFormat('j M Y'), $m?->education, $m?->job];
        $rows = [];
        foreach ($slots as $label => $m) {
            $rows[] = $row($label, $m);
        }
        foreach ($extra as $m) {
            $rows[] = $row($m->relation ?: 'Lainnya', $m);
        }

        return $rows;
    }

    /**
     * Tiga blok pekerjaan seperti template (Sekarang/Terakhir, Sebelumnya 1, Sebelumnya 2), paling baru dulu. Blok tanpa
     * data tetap dicetak kosong (titik-titik). Maksimal 3 (form juga dibatasi 3). Tanggal dicetak "dd / mm / yyyy".
     */
    private static function works(Candidate $c): array
    {
        $money = fn ($v) => $v !== null && $v !== '' ? number_format((float) $v, 0, ',', '.') : null;
        $date = fn (?Carbon $d) => $d ? $d->format('d') . ' / ' . $d->format('m') . ' / ' . $d->format('Y') : null;

        $list = $c->workExperiences
            ->sortByDesc(fn ($w) => [(int) $w->is_current, $w->start_date?->timestamp ?? 0])
            ->take(3)->values();

        return collect(range(0, 2))->map(function ($i) use ($list, $money, $date) {
            $w = $list[$i] ?? null;

            return [
                'title' => $i === 0 ? 'Pekerjaan Sekarang / Terakhir' : 'Pekerjaan Sebelumnya ' . $i,
                'company' => $w?->company_name,
                'business' => $w?->business_type,
                'address' => $w?->company_address,
                'position' => $w?->position,
                'from' => $date($w?->start_date),
                'to' => $w ? ($w->is_current ? 'sekarang' : $date($w->end_date)) : null,
                'salary_start' => $money($w?->salary_start),
                'salary_end' => $money($w?->salary),
                'reason' => $w?->resign_reason,
                'job_description' => $w?->job_description,
                'org' => $w?->org_position_description,
            ];
        })->all();
    }
}
