<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Support\BiodataData;
use App\Support\BiodataFields;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CandidateProfileController extends Controller
{
    /**
     * Candidate milik user, dibuat saat ini juga kalau biodata sudah terbuka (jadwal wawancara pertama terbit, atau
     * lamaran sudah melewati wawancara). Lazy-create karena HRIS menulis DB langsung — event model bisa tidak jalan.
     */
    private function currentCandidate(): Candidate
    {
        $application = Auth::user()->biodataApplication();
        abort_if(! $application, 403, 'Biodata baru bisa diisi setelah Anda dijadwalkan wawancara.');

        return Candidate::ensureFor($application);
    }

    /** Persetujuan hanya untuk tahap onboarding; sebelum itu halamannya tidak relevan. */
    private function onboarding(Candidate $candidate): bool
    {
        return (bool) $candidate->jobApplication?->onboardingStage();
    }

    /** Onboarding dan biodata sudah lengkap: biodata tidak diubah lagi, hanya BPJS/rekening yang diisi. Belum lengkap: form terbuka penuh. */
    private function biodataFrozen(Candidate $candidate, bool $onboarding): bool
    {
        return $onboarding && $candidate->biodataComplete();
    }

    public function edit(): RedirectResponse|View
    {
        $candidate = $this->currentCandidate();
        $onboarding = $this->onboarding($candidate);

        if ($onboarding && $candidate->agreementsPending()) {
            return redirect()->route('persetujuan.edit')
                ->with('warning', 'Setujui semua persetujuan dulu sebelum mengisi data diri.');
        }

        $candidate->load([
            'familyMembers', 'educations', 'trainings', 'organizations',
            'workExperiences', 'references', 'additionalInfo', 'selfAssessment',
            'documents',
        ]);

        // Tahap biodata: tanpa tab 3 (BPJS & Rekening) dan 12 (Dokumen). Onboarding+: semua tab.
        $sections = BiodataFields::sections($candidate->biodataCompany());
        $checklist = $onboarding ? $candidate->requiredDataChecklist(true) : $candidate->biodataChecklist();

        $locked = (bool) $candidate->jobApplication?->biodataLocked();

        $frozen = $this->biodataFrozen($candidate, $onboarding);

        return view('candidate-profile.edit', compact('candidate', 'onboarding', 'sections', 'checklist', 'locked', 'frozen'));
    }

    public function agreements(): View|RedirectResponse
    {
        if (! $this->onboarding($this->currentCandidate())) {
            return redirect()->route('data-diri.edit');
        }

        $candidate = $this->currentCandidate()->load([
            'agreements',
            'jobApplication.jobOpening.company.agreements', 'jobApplication.jobOpening.agreements',
        ]);

        $effectiveAgreements = $candidate->jobApplication?->jobOpening?->effectiveAgreements() ?? collect();

        // Sudah lengkap -> halaman jadi read-only (kandidat masih bisa baca naskahnya).
        $locked = ! $candidate->agreementsPending();

        return view('candidate-profile.agreements', compact('candidate', 'effectiveAgreements', 'locked'));
    }

    public function updateAgreements(Request $request): RedirectResponse
    {
        $candidate = $this->currentCandidate();

        if (! $this->onboarding($candidate)) {
            return redirect()->route('data-diri.edit');
        }

        // Sudah lengkap -> read-only, tidak menerima perubahan lagi.
        if (! $candidate->agreementsPending()) {
            return redirect()->route('persetujuan.edit');
        }

        $request->validate(['agreements' => ['array']]);

        $effective = $candidate->jobApplication?->jobOpening?->effectiveAgreements() ?? collect();

        // Semua persetujuan wajib dicentang — tidak ada simpan sebagian.
        $allChecked = $effective->every(function ($item) use ($request) {
            $key = $item['company_agreement_id'] ? 'c:' . $item['company_agreement_id'] : 'j:' . $item['job_opening_agreement_id'];

            return $request->boolean("agreements.{$key}");
        });

        if (! $allChecked) {
            return redirect()->route('persetujuan.edit')
                ->with('warning', 'Centang semua persetujuan dulu sebelum menyimpan.');
        }

        DB::transaction(function () use ($candidate, $request, $effective) {
            foreach ($effective as $item) {
                $col = $item['company_agreement_id'] ? 'company_agreement_id' : 'job_opening_agreement_id';
                $id  = $item['company_agreement_id'] ?: $item['job_opening_agreement_id'];

                // Snapshot judul + naskah master saat disetujui — lepas dari master.
                $candidate->agreements()->updateOrCreate(
                    [$col => $id],
                    [
                        'title' => $item['title'], 'content' => $item['content'],
                        'agreed_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
                    ]
                );
            }
        });

        return redirect()->route('data-diri.edit')
            ->with('success', 'Persetujuan tersimpan. Lanjut lengkapi data diri kandidat.');
    }

    public function update(Request $request): RedirectResponse
    {
        $candidate = $this->currentCandidate();
        $onboarding = $this->onboarding($candidate);

        if ($candidate->jobApplication?->biodataLocked()) {
            return redirect()->route('data-diri.edit')
                ->with('warning', 'Biodata tidak bisa diubah lagi pada tahap offering.');
        }

        if ($onboarding && $candidate->agreementsPending()) {
            return redirect()->route('persetujuan.edit')
                ->with('warning', 'Setujui semua persetujuan dulu sebelum mengisi data diri.');
        }

        // Onboarding: biodata dibekukan, hanya BPJS/rekening yang bisa diisi (dokumen KTP/KK nanti di HRIS).
        if ($this->biodataFrozen($candidate, $onboarding)) {
            $only = $request->validate([
                'no_bpjskes' => ['nullable', 'string', 'max:50'], 'faskes' => ['nullable', 'string', 'max:255'],
                'no_bpjstk' => ['nullable', 'string', 'max:50'], 'company_bpjstk' => ['nullable', 'string', 'max:255'],
                'bsi_bank_number' => ['nullable', 'string', 'max:50'],
            ], $this->validationMessages(), $this->validationAttributes());
            $candidate->update(Arr::only($only, ['no_bpjskes', 'faskes', 'no_bpjstk', 'company_bpjstk', 'bsi_bank_number']));

            return redirect()->route('data-diri.edit')->with('success', 'Data diri berhasil disimpan.');
        }

        // Checkbox yang tidak dicentang tidak ikut terkirim; STR seumur hidup tidak punya tanggal.
        if ($request->has('no_str')) {
            $lifetime = $request->boolean('str_seumur_hidup');
            $request->merge(['str_seumur_hidup' => $lifetime] + ($lifetime ? ['str_berlaku_mulai' => null, 'str_berlaku_sampai' => null] : []));
        }

        $data = $request->validate([
            'nickname'              => ['nullable', 'string', 'max:100'],
            'gender'                => ['nullable', 'in:Laki-laki,Perempuan'],
            'birth_place'           => ['nullable', 'string', 'max:255'],
            'birth_date'            => ['nullable', 'date', 'before_or_equal:today'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'biodata_confirmed'     => ['nullable', 'boolean'],
            'no_kk'                 => ['nullable', 'string', 'max:16'],
            'blood_type'            => ['nullable', 'string', 'max:5'],
            'body_height_weight'    => ['nullable', 'string', 'max:100'],
            'religion'              => ['nullable', 'string', 'max:100'],
            'nationality'           => ['nullable', 'string', 'max:100'],
            'status_marital'        => ['nullable', 'in:Belum Menikah,Sudah Menikah,Janda,Duda'],
            'marriage_date'         => ['nullable', 'date'],
            'mother_name'           => ['nullable', 'string', 'max:255'],
            'no_str'                => ['nullable', 'string', 'max:100'],
            'str_seumur_hidup'      => ['boolean'],
            'str_berlaku_mulai'     => ['nullable', 'date'],
            'str_berlaku_sampai'    => ['nullable', 'date', 'after_or_equal:str_berlaku_mulai'],
            'ktp_address'           => ['nullable', 'string'],
            'village'               => ['nullable', 'string', 'max:100'],
            'district'              => ['nullable', 'string', 'max:100'],
            'city'                  => ['nullable', 'string', 'max:100'],
            'province'              => ['nullable', 'string', 'max:100'],
            'social_media_accounts' => ['nullable', 'string', 'max:255'],
            'no_bpjskes'            => ['nullable', 'string', 'max:50'],
            'faskes'                => ['nullable', 'string', 'max:255'],
            'no_bpjstk'             => ['nullable', 'string', 'max:50'],
            'company_bpjstk'        => ['nullable', 'string', 'max:255'],
            'bsi_bank_number'       => ['nullable', 'string', 'max:50'],

            'family'         => ['array'],
            'family.*.relation'   => ['required_with:family', 'string', 'max:100'],
            'family.*.gender'     => ['nullable', 'string'],
            'family.*.name'       => ['nullable', 'string', 'max:255'],
            'family.*.birth_date' => ['nullable', 'date'],
            'family.*.education'  => ['nullable', 'string', 'max:100'],
            'family.*.job'        => ['nullable', 'string', 'max:100'],
            'family.*.phone'      => ['nullable', 'string', 'max:30'],

            'education'          => ['array'],
            'education.*.level'       => ['required_with:education', 'string', 'max:50'],
            'education.*.school_name' => ['nullable', 'string', 'max:255'],
            'education.*.major'       => ['nullable', 'string', 'max:255'],
            'education.*.gpa'         => ['nullable', 'numeric'],
            'education.*.start_year'  => ['nullable', 'integer'],
            'education.*.end_year'    => ['nullable', 'integer'],

            'training'            => ['array', 'max:5'], // template biodata: 5 baris pelatihan
            'training.*.type'        => ['nullable', 'string', 'max:255'],
            'training.*.institution' => ['nullable', 'string', 'max:255'],
            'training.*.material'    => ['nullable', 'string', 'max:255'],
            'training.*.duration'    => ['nullable', 'string', 'max:100'],
            'training.*.location'    => ['nullable', 'string', 'max:255'],

            'organization'                    => ['array', 'max:5'], // template biodata: 5 baris organisasi
            'organization.*.organization_name' => ['nullable', 'string', 'max:255'],
            'organization.*.field'             => ['nullable', 'string', 'max:255'],
            'organization.*.position'          => ['nullable', 'string', 'max:255'],
            'organization.*.duration'          => ['nullable', 'string', 'max:100'],
            'organization.*.location'          => ['nullable', 'string', 'max:255'],

            'work'                    => ['array', 'max:3'], // template biodata memuat 3 blok pekerjaan
            'work.*.company_name'    => ['nullable', 'string', 'max:255'],
            'work.*.business_type'   => ['nullable', 'string', 'max:255'],
            'work.*.company_address' => ['nullable', 'string'],
            'work.*.position'        => ['nullable', 'string', 'max:255'],
            'work.*.start_date'      => ['nullable', 'date'],
            'work.*.end_date'        => ['nullable', 'date'],
            'work.*.salary'          => ['nullable', 'numeric'],
            'work.*.salary_start'      => ['nullable', 'numeric'],
            'work.*.job_description' => ['nullable', 'string'],
            'work.*.org_position_description' => ['nullable', 'string'],
            'work.*.resign_reason'   => ['nullable', 'string'],
            'work.*.is_current'      => ['nullable', 'boolean'],

            'reference'            => ['array', 'max:3'], // template biodata: 3 baris referensi
            'reference.*.name'     => ['nullable', 'string', 'max:255'],
            'reference.*.address'  => ['nullable', 'string'],
            'reference.*.job'      => ['nullable', 'string', 'max:255'],
            'reference.*.phone'    => ['nullable', 'string', 'max:30'],
            'reference.*.relation' => ['nullable', 'string', 'max:100'],

            'additional_info' => ['array'],
            'additional_info.allow_reference_check'    => ['nullable', 'boolean'],
            'additional_info.has_disease'               => ['nullable', 'boolean'],
            'additional_info.disease_notes'              => ['nullable', 'string'],
            'additional_info.hobbies'                     => ['nullable', 'string'],
            'additional_info.languages'                   => ['nullable', 'string'],
            'additional_info.last_facilities'             => ['nullable', 'string'],
            'additional_info.expected_salary_facilities'  => ['nullable', 'string'],
            'additional_info.available_start_date'        => ['nullable', 'date'],

            'self_assessment' => ['array'],
            'self_assessment.strengths'           => ['nullable', 'string'],
            'self_assessment.weaknesses'          => ['nullable', 'string'],
            'self_assessment.satisfaction_level'  => ['nullable', 'in:very_unsatisfied,unsatisfied,less_satisfied,satisfied,very_satisfied'],
            'self_assessment.reason'              => ['nullable', 'string'],

            'documents'              => ['array'],
            'documents.*.doc_type'   => ['required_with:documents', 'string', 'max:100'],
            'documents.*.label'      => ['nullable', 'string', 'max:255'],
            'documents.*.file'       => ['nullable', 'file', 'max:5120'],
        ], $this->validationMessages(), $this->validationAttributes());

        DB::transaction(function () use ($candidate, $data, $request, $onboarding) {
            $candidate->update(collect($data)->only($this->savableColumns($candidate, $onboarding))->all());

            if ($request->boolean('biodata_confirmed') && ! $candidate->biodata_confirmed_at) {
                $candidate->update(['biodata_confirmed_at' => now()]);
            }

            // Replace-all untuk tiap koleksi berulang — sederhana, tidak perlu diffing.
            $candidate->familyMembers()->delete();
            // Ayah selalu Laki-laki, Ibu selalu Perempuan (juga di server, bukan hanya di form).
            $family = collect($data['family'] ?? [])->map(function ($m) {
                $rel = mb_strtolower(trim((string) ($m['relation'] ?? '')));
                if (in_array($rel, ['ayah', 'bapak'], true)) $m['gender'] = 'Laki-laki';
                if ($rel === 'ibu') $m['gender'] = 'Perempuan';

                return $m;
            })->values()->all();
            $candidate->familyMembers()->createMany($family);

            $candidate->educations()->delete();
            $candidate->educations()->createMany(array_values($data['education'] ?? []));

            $candidate->trainings()->delete();
            $candidate->trainings()->createMany(array_values($data['training'] ?? []));

            $candidate->organizations()->delete();
            $candidate->organizations()->createMany(array_values($data['organization'] ?? []));

            $candidate->workExperiences()->delete();
            // Uraian tugas dan posisi organisasi hanya ditanyakan di rumah sakit.
            $work = collect($data['work'] ?? [])->map(fn ($w) => $candidate->isHospital() ? $w : Arr::except($w, ['job_description', 'org_position_description']))->values()->all();
            $candidate->workExperiences()->createMany($work);

            $candidate->references()->delete();
            $candidate->references()->createMany(array_values($data['reference'] ?? []));

            if (! empty($data['additional_info'])) {
                $candidate->additionalInfo()->updateOrCreate([], $data['additional_info']);
            }

            if (! empty($data['self_assessment'])) {
                $candidate->selfAssessment()->updateOrCreate([], $data['self_assessment']);
            }

            // Dokumen hanya di tahap onboarding (tab Dokumen tidak ada di tahap biodata).
            foreach ($onboarding ? $request->file('documents', []) : [] as $i => $doc) {
                if (! isset($doc['file'])) continue;
                $docType = $data['documents'][$i]['doc_type'];
                $path = $doc['file']->store("candidates/{$candidate->id}/{$docType}", 'local');

                CandidateDocument::create([
                    'candidate_id'   => $candidate->id,
                    'doc_type'       => $docType,
                    'document_label' => $data['documents'][$i]['label'] ?? null,
                    'file_path'      => $path,
                ]);
            }

        });

        return redirect()->route('data-diri.edit')->with('success', 'Data diri berhasil disimpan.');
    }

    /** Pesan galat berbahasa Indonesia: bawaan Laravel berbahasa Inggris dan menyebut nama teknis ("work.0.salary"). */
    private function validationMessages(): array
    {
        return [
            'required_with' => ':attribute wajib diisi.',
            'max.string'    => ':attribute terlalu panjang (maksimal :max karakter).',
            'max.array'     => ':attribute maksimal :max baris.',
            'max.file'      => ':attribute terlalu besar (maksimal :max KB).',
            'numeric'       => ':attribute harus berupa angka.',
            'integer'       => ':attribute harus berupa bilangan bulat.',
            'date'          => ':attribute bukan tanggal yang valid.',
            'in'            => ':attribute tidak valid, pilih salah satu pilihan yang tersedia.',
            'before_or_equal' => ':attribute tidak boleh melewati hari ini.',
            'after_or_equal'  => ':attribute tidak boleh sebelum tanggal mulai.',
            'boolean'       => ':attribute tidak valid.',
            'array'         => ':attribute tidak valid.',
            'file'          => ':attribute harus berupa berkas.',
        ];
    }

    /** Nama isian yang mudah dipahami; baris berulang disebut "baris ke-N" lewat placeholder :position. */
    private function validationAttributes(): array
    {
        $out = [
            'nickname' => 'Nama panggilan', 'gender' => 'Jenis kelamin', 'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir',
            'emergency_contact_name' => 'Nama kontak darurat', 'emergency_contact_phone' => 'No. telepon kontak darurat',
            'no_kk' => 'No. KK', 'blood_type' => 'Golongan darah', 'body_height_weight' => 'Tinggi/berat badan', 'religion' => 'Agama',
            'nationality' => 'Kewarganegaraan', 'status_marital' => 'Status pernikahan', 'marriage_date' => 'Tanggal pernikahan',
            'mother_name' => 'Nama ibu kandung', 'no_str' => 'No. STR', 'str_berlaku_mulai' => 'Tanggal mulai berlaku STR',
            'str_berlaku_sampai' => 'Tanggal berakhir STR', 'ktp_address' => 'Alamat KTP', 'village' => 'Kelurahan/Desa', 'district' => 'Kecamatan',
            'city' => 'Kota/Kabupaten', 'province' => 'Provinsi', 'social_media_accounts' => 'Akun media sosial',
            'no_bpjskes' => 'No. BPJS Kesehatan', 'faskes' => 'Faskes', 'no_bpjstk' => 'No. BPJS Ketenagakerjaan',
            'company_bpjstk' => 'Perusahaan BPJS Ketenagakerjaan', 'bsi_bank_number' => 'No. rekening',
            'additional_info.available_start_date' => 'Tanggal bersedia mulai bekerja', 'self_assessment.satisfaction_level' => 'Tingkat kepuasan',
        ];

        $rows = [
            'family' => ['Data keluarga', ['relation' => 'hubungan', 'gender' => 'jenis kelamin', 'name' => 'nama', 'birth_date' => 'tanggal lahir', 'education' => 'pendidikan', 'job' => 'pekerjaan', 'phone' => 'telepon']],
            'education' => ['Riwayat pendidikan', ['level' => 'jenjang', 'school_name' => 'nama sekolah', 'major' => 'jurusan', 'gpa' => 'IPK/nilai', 'start_year' => 'tahun masuk', 'end_year' => 'tahun lulus']],
            'training' => ['Riwayat pelatihan', ['type' => 'jenis', 'institution' => 'penyelenggara', 'material' => 'materi', 'duration' => 'lama', 'location' => 'lokasi']],
            'organization' => ['Pengalaman organisasi', ['organization_name' => 'nama organisasi', 'field' => 'bidang', 'position' => 'jabatan', 'duration' => 'lama', 'location' => 'lokasi']],
            'work' => ['Pengalaman kerja', ['company_name' => 'nama perusahaan', 'business_type' => 'bidang usaha', 'company_address' => 'alamat', 'position' => 'posisi', 'start_date' => 'tanggal mulai', 'end_date' => 'tanggal selesai', 'salary_start' => 'gaji awal', 'salary' => 'gaji akhir', 'resign_reason' => 'alasan berhenti']],
            'reference' => ['Referensi', ['name' => 'nama', 'address' => 'alamat', 'job' => 'pekerjaan', 'phone' => 'telepon', 'relation' => 'hubungan']],
        ];
        foreach ($rows as $prefix => [$title, $cols]) {
            foreach ($cols as $key => $label) {
                $out["{$prefix}.*.{$key}"] = "{$title} baris ke-:position ({$label})";
            }
        }
        foreach (['family' => 'Data keluarga', 'education' => 'Riwayat pendidikan', 'training' => 'Riwayat pelatihan', 'organization' => 'Pengalaman organisasi', 'work' => 'Pengalaman kerja', 'reference' => 'Referensi'] as $prefix => $title) {
            $out[$prefix] = $title;
        }

        return $out;
    }

    /**
     * Kolom candidates yang boleh disimpan: isian yang dimatikan master perusahaan tidak ditimpa, dan kolom
     * BPJS/rekening hanya di tahap onboarding (tabnya tidak tampil sebelum itu).
     */
    private function savableColumns(Candidate $candidate, bool $onboarding): array
    {
        $columns = [
            'nickname', 'gender', 'birth_place', 'birth_date', 'no_kk', 'blood_type', 'body_height_weight',
            'religion', 'nationality', 'status_marital', 'marriage_date', 'mother_name',
            'emergency_contact_name', 'emergency_contact_phone', 'no_str',
            'str_seumur_hidup', 'str_berlaku_mulai', 'str_berlaku_sampai',
            'ktp_address', 'village', 'district', 'city', 'province', 'social_media_accounts',
        ];
        if ($onboarding) {
            array_push($columns, 'no_bpjskes', 'faskes', 'no_bpjstk', 'company_bpjstk', 'bsi_bank_number');
        }

        $enabled = BiodataFields::flat($candidate->biodataCompany());
        $disabled = collect(config('biodata.fields'))
            ->reject(fn ($def, $key) => isset($enabled[$key]))
            ->flatMap(fn ($def, $key) => $def['columns'] ?? [$key])
            ->all();

        return array_values(array_diff($columns, $disabled, array_keys($candidate->lockedFields())));
    }

    private function biodataPdf(): array
    {
        $candidate = $this->currentCandidate();
        $pdf = Pdf::loadView('candidate-profile.biodata-pdf', ['d' => BiodataData::for($candidate)])->setPaper('a4');

        return [$pdf, 'biodata-' . (Str::slug($candidate->name) ?: 'pelamar') . '.pdf'];
    }

    /** Pratinjau biodata sebagai PDF inline (tab baru), dirender dari isian (BiodataData). */
    public function previewBiodata()
    {
        [$pdf, $file] = $this->biodataPdf();

        return $pdf->stream($file);
    }

    /** Unduh biodata sebagai file PDF. */
    public function downloadBiodata()
    {
        [$pdf, $file] = $this->biodataPdf();

        return $pdf->download($file);
    }

    /**
     * Kandidat: unggah biodata (hasil cetak PDF) yang sudah ditandatangani, seperti surat penawaran.
     * Hanya sekali; setelah itu tidak bisa diunggah ulang.
     */
    public function uploadSignedBiodata(Request $request): RedirectResponse
    {
        $candidate = $this->currentCandidate();

        if ($candidate->jobApplication?->biodataLocked() || $this->biodataFrozen($candidate, $this->onboarding($candidate))) {
            return back()->with('warning', 'Biodata sudah dikunci, unggahan tidak bisa diganti lagi.');
        }

        // Pengiriman hanya sekali (seperti surat penawaran).
        if ($candidate->biodata_signed_path) {
            return back()->with('warning', 'Biodata bertanda tangan sudah diunggah dan tidak bisa diunggah ulang.');
        }

        $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);

        $candidate->update([
            'biodata_signed_path' => $request->file('file')->store("candidates/{$candidate->id}/biodata-signed", 'local'),
            'biodata_signed_at'   => now(),
        ]);

        return back()->with('success', 'Biodata bertanda tangan berhasil diunggah.');
    }

    public function downloadSignedBiodata()
    {
        $candidate = $this->currentCandidate();
        abort_unless($candidate->biodata_signed_path && Storage::disk('local')->exists($candidate->biodata_signed_path), 404);

        return Storage::disk('local')->response($candidate->biodata_signed_path);
    }

    /** Admin/HR: biodata bertanda tangan milik satu lamaran (hak akses sama dengan surat penawaran). */
    public function adminSignedBiodata(\App\Models\JobApplication $application)
    {
        $user = Auth::user();
        abort_unless(
            $user->hasAnyRole(['super_admin', 'Super Admin']) || ($user->is_staff && $user->company_id === $application->jobOpening->company_id),
            403
        );
        $path = Candidate::where('user_id', $application->user_id)->value('biodata_signed_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function downloadDocument(CandidateDocument $document)
    {
        $user = Auth::user();
        $isOwner = $document->candidate->user_id === $user->id;
        $isSuperAdmin = $user->hasAnyRole(['super_admin', 'Super Admin']);
        $isCompanyStaff = $user->is_staff && $user->company_id === $document->candidate->jobApplication?->jobOpening?->company_id;

        abort_unless($isOwner || $isSuperAdmin || $isCompanyStaff, 403);

        return Storage::disk('local')->response($document->file_path);
    }
}
