<?php

namespace App\Http\Controllers\Api;

use App\Models\ApplicationNotification;
use App\Models\Company;
use App\Models\CompanyMessageTemplate;
use App\Models\InterviewInvitee;
use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobOpeningStageLog;
use App\Models\McuDoctorExamination;
use App\Models\McuInvitee;
use App\Models\McuLabResult;
use App\Models\McuNurseAssessment;
use App\Models\McuRadiologyResult;
use App\Models\McuSchedule;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrisIntegrationController extends Controller
{
    /**
     * Transisi status yang boleh digerakkan HRIS (HRIS pegang seluruh proses internal;
     * `applied` cuma dari pelamar melamar, `passed`/`rejected` terminal). Dipakai
     * updateApplicationStatus() — di luar peta ini balas 422.
     */
    private const HRIS_TRANSITIONS = [
        'applied'     => ['screening', 'rejected'],
        'screening'   => ['shortlisted', 'rejected'],
        'shortlisted' => ['interview', 'rejected'],
        'interview'   => ['offering', 'rejected'],
        'offering'    => ['mcu', 'onboarding', 'rejected'],
        'mcu'         => ['onboarding', 'rejected'],
        'onboarding'  => ['passed', 'rejected'],
    ];
    public function healthCheck(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function applicantCounts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|string',
        ]);

        $counts = JobOpening::whereIn('hris_job_opening_id', $data['ids'])
            ->withCount('applications')
            ->get(['id', 'hris_job_opening_id', 'applications_count'])
            ->mapWithKeys(fn ($j) => [$j->hris_job_opening_id => $j->applications_count]);

        return response()->json(['counts' => $counts]);
    }

    /**
     * Lowongan yang dibuat manual di sistem ini dan BELUM terhubung ke HRIS
     * (hris_job_opening_id null). Dipakai HRIS buat kasih tahu adminnya ada lowongan
     * di luar yang belum tercatat di HRIS. Read-only, tidak ada paginasi — jumlah
     * lowongan manual per grup kecil.
     */
    public function unlinkedJobOpenings(): JsonResponse
    {
        $openings = JobOpening::whereNull('hris_job_opening_id')
            ->with('company:id,hris_code,name')
            ->latest()
            ->get(['id', 'company_id', 'title', 'quota', 'max_applicants', 'applications_deadline', 'is_active', 'created_at']);

        return response()->json([
            'data' => $openings->map(fn (JobOpening $j) => [
                'id'                    => $j->id,
                'title'                 => $j->title,
                'company_name'          => $j->company?->name,
                'hris_company_code'     => $j->company?->hris_code,
                'quota'                 => $j->quota,
                'max_applicants'        => $j->max_applicants,
                'applications_deadline' => $j->applications_deadline?->format('Y-m-d'),
                'is_active'             => $j->is_active,
                'created_at'            => $j->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * HRIS push data perusahaan ke sini (upsert by hris_code). Menutup blocker "company
     * harus dibuat manual di admin panel Rekrutmen dulu sebelum HRIS bisa push lowongan".
     * Cuma set hris_code/name/is_active — branding (logo, warna) & requires_mcu tetap
     * dikelola admin Rekrutmen, tidak ditimpa tiap push.
     */
    public function upsertCompany(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hris_code' => 'required|string|max:10',
            'name'      => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $company = Company::firstOrNew(['hris_code' => $data['hris_code']]);
        $isNew = ! $company->exists;

        $company->fill([
            'name'      => $data['name'],
            'is_active' => $data['is_active'],
        ]);
        $company->save();

        return response()->json([
            'message'   => $isNew ? 'Created' : 'Updated',
            'id'        => $company->id,
            'hris_code' => $company->hris_code,
        ], $isNew ? 201 : 200);
    }

    /**
     * Daftar lamaran untuk satu lowongan HRIS — dipakai HRIS buat lihat progres tiap
     * kandidat per lowongan (bukan cuma jumlah total lewat applicantCounts) DAN buat tahu
     * job_application_id mana yang sudah `passed` sebelum tarik data kandidatnya (showCandidate).
     * Filter opsional `?status=passed`.
     */
    public function jobOpeningApplications(Request $request, string $hrisJobOpeningId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'nullable|string|in:applied,screening,shortlisted,interview,offering,mcu,onboarding,passed,rejected',
        ]);

        // Cocok by hris_job_opening_id (lowongan dari HRIS) ATAU id lowongan Rekrutmen sendiri
        // (lowongan manual/belum terhubung — HRIS tetap bisa kelola pelamarnya).
        $opening = JobOpening::where('hris_job_opening_id', $hrisJobOpeningId)
            ->orWhere('id', $hrisJobOpeningId)
            ->first();

        if (! $opening) {
            return response()->json(['message' => 'Job opening not found: ' . $hrisJobOpeningId], 404);
        }

        $applications = $opening->applications()
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->with('user:id,name,email')
            ->latest('updated_at')
            ->get(['id', 'user_id', 'status', 'updated_at']);

        return response()->json([
            'job_opening_title' => $opening->title,
            'hris_linked'       => $opening->hris_job_opening_id !== null,
            'data' => $applications->map(fn (JobApplication $a) => [
                'job_application_id' => $a->id,
                'status'             => $a->status,
                'applicant_name'     => $a->user?->name,
                'applicant_email'    => $a->user?->email,
                'updated_at'         => $a->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Info dasar 1 lamaran + riwayat status — selalu tersedia (beda dari showCandidate yang
     * butuh row candidate). Dipakai halaman detail pelamar di HRIS buat timeline status.
     */
    public function showApplication(string $jobApplicationId): JsonResponse
    {
        $application = JobApplication::with([
            'user:id,name,email',
            'jobOpening:id,hris_job_opening_id,title',
            'statusLogs',
            'interviewInvitee.schedule',
        ])->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        $interview = $application->interviewInvitee?->schedule;

        return response()->json([
            'job_application_id' => $application->id,
            'status'             => $application->status,
            'applicant_name'     => $application->user?->name,
            'applicant_email'    => $application->user?->email,
            'job_opening'        => [
                'hris_job_opening_id' => $application->jobOpening?->hris_job_opening_id,
                'title'               => $application->jobOpening?->title,
            ],
            'has_candidate_data' => $application->candidate()->exists(),
            'requires_mcu'       => (bool) ($application->jobOpening?->company?->requires_mcu ?? true),
            'quiz_result'        => $application->quiz_result,
            'interview_result'   => $application->interview_result,
            'offering'           => [
                'recruiter_uploaded_at' => $application->offering_letter_uploaded_at?->toIso8601String(),
                'signed_uploaded_at'    => $application->offering_letter_signed_at?->toIso8601String(),
            ],
            'interview'          => $interview ? [
                'date'     => $interview->interview_date?->format('Y-m-d'),
                'time'     => $interview->interview_time ? substr($interview->interview_time, 0, 5) : null,
                'location' => $interview->location,
                'notes'    => $interview->notes,
            ] : null,
            'status_logs'        => $application->statusLogs->map(fn ($l) => [
                'from' => $l->from_status,
                'to'   => $l->to_status,
                'at'   => $l->changed_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Jadwalkan / ubah jadwal wawancara + pindahkan ke status `interview`. 1 schedule + 1
     * invitee per lamaran (bukan batch seperti WawancaraPage Filament). Scheduling = implisit
     * lulus tes, jadi `quiz_result` di-set 'lulus' kalau masih kosong.
     */
    public function scheduleInterview(Request $request, string $jobApplicationId): JsonResponse
    {
        $data = $request->validate([
            'interview_date' => 'required|date',
            'interview_time' => 'nullable|date_format:H:i',
            'location'       => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $application = JobApplication::with('interviewInvitee.schedule')->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if (! in_array($application->status, ['shortlisted', 'interview'], true)) {
            return response()->json(['message' => "Wawancara cuma bisa dijadwalkan dari status 'shortlisted' atau 'interview' (sekarang '{$application->status}')."], 422);
        }

        $from = $application->status;
        $schedule = $application->interviewInvitee?->schedule;

        if ($schedule) {
            $schedule->update([
                'interview_date' => $data['interview_date'],
                'interview_time' => $data['interview_time'] ?? null,
                'location'       => $data['location'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);
        } else {
            $schedule = InterviewSchedule::create([
                'job_opening_id' => $application->job_opening_id,
                'interview_date' => $data['interview_date'],
                'interview_time' => $data['interview_time'] ?? null,
                'location'       => $data['location'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'status'         => 'published',
            ]);
            InterviewInvitee::create([
                'interview_schedule_id' => $schedule->id,
                'job_application_id'    => $application->id,
            ]);
        }

        if ($application->quiz_result === null) {
            $application->quiz_result = 'lulus';
        }
        $application->status = 'interview';
        $application->save();

        ApplicationNotification::createDraftFor($application, CompanyMessageTemplate::EVENT_INTERVIEW_SCHEDULED, [
            'interview_date' => $schedule->interview_date?->translatedFormat('d F Y'),
            'interview_time' => $data['interview_time'] ?? null,
            'location'       => $schedule->location,
            'notes'          => $schedule->notes,
        ]);

        if ($from !== 'interview') {
            JobOpeningStageLog::record($application->job_opening_id, $from, 'interview', 1);
        }

        return response()->json([
            'message' => 'Scheduled',
            'status'  => 'interview',
            'interview' => [
                'date'     => $schedule->interview_date?->format('Y-m-d'),
                'time'     => $data['interview_time'] ?? null,
                'location' => $schedule->location,
                'notes'    => $schedule->notes,
            ],
        ]);
    }

    /**
     * Catat hasil wawancara → `offering` (lulus) atau `rejected` (tidak_lulus).
     */
    public function submitInterviewResult(Request $request, string $jobApplicationId): JsonResponse
    {
        $data = $request->validate([
            'result' => 'required|in:lulus,tidak_lulus',
        ]);

        $application = JobApplication::find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if ($application->status !== 'interview') {
            return response()->json(['message' => "Hasil wawancara cuma bisa dicatat dari status 'interview' (sekarang '{$application->status}')."], 422);
        }

        if ($data['result'] === 'lulus') {
            $application->update(['interview_result' => 'lulus', 'status' => 'offering']);
            JobOpeningStageLog::record($application->job_opening_id, 'interview', 'offering', 1);
        } else {
            $application->update(['interview_result' => 'tidak_lulus', 'status' => 'rejected']);
            JobOpeningStageLog::record($application->job_opening_id, 'interview', 'rejected', 1);
            ApplicationNotification::createDraftFor($application, CompanyMessageTemplate::EVENT_INTERVIEW_REJECTED);
        }

        return response()->json([
            'message'          => 'Recorded',
            'status'           => $application->status,
            'interview_result' => $application->interview_result,
        ]);
    }

    /**
     * Upload surat penawaran (dari recruiter) — multipart `file`. Status wajib `offering`.
     * File masuk disk `local` privat, pola sama OfferingLetterPage Filament.
     */
    public function uploadOfferingLetter(Request $request, string $jobApplicationId): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $application = JobApplication::find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if ($application->status !== 'offering') {
            return response()->json(['message' => "Offering letter cuma bisa diunggah saat status 'offering' (sekarang '{$application->status}')."], 422);
        }

        $path = $request->file('file')->store("offering-letters/{$application->id}/recruiter", 'local');

        $application->update([
            'offering_letter_path'        => $path,
            'offering_letter_uploaded_at' => now(),
        ]);

        return response()->json(['message' => 'Uploaded', 'uploaded_at' => $application->offering_letter_uploaded_at?->toIso8601String()]);
    }

    /** Stream surat penawaran: `recruiter` (dari recruiter) atau `signed` (dari kandidat). */
    public function downloadOfferingLetter(string $jobApplicationId, string $type): StreamedResponse
    {
        $application = JobApplication::find($jobApplicationId);

        abort_unless($application, 404, 'Job application not found');

        $path = $type === 'signed' ? $application->offering_letter_signed_path : $application->offering_letter_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'File tidak ada');

        return Storage::disk('local')->response($path);
    }

    /**
     * Lanjut setelah offering — ke `mcu` (kalau company `requires_mcu`) atau `onboarding`.
     * Guard: status `offering` + surat bertanda tangan kandidat sudah masuk.
     */
    public function advanceFromOffering(string $jobApplicationId): JsonResponse
    {
        $application = JobApplication::with('jobOpening.company')->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if ($application->status !== 'offering') {
            return response()->json(['message' => "Status sekarang '{$application->status}', bukan 'offering'."], 422);
        }

        if ($application->offering_letter_signed_path === null) {
            return response()->json(['message' => 'Kandidat belum mengunggah surat penawaran bertanda tangan.'], 422);
        }

        $next = ($application->jobOpening?->company?->requires_mcu ?? true) ? 'mcu' : 'onboarding';
        $application->update(['status' => $next]);
        JobOpeningStageLog::record($application->job_opening_id, 'offering', $next, 1);

        return response()->json(['message' => 'Advanced', 'status' => $next]);
    }

    /**
     * Field per stasiun MCU — dipakai updateMcuStation() untuk validasi & mass-assign.
     * Semua nullable kecuali 6 vital sign perawat.
     */
    private const MCU_FIELDS = [
        'nurse' => [
            'tinggi_badan', 'berat_badan', 'tekanan_darah', 'nadi', 'suhu', 'pernafasan',
            'keluhan_kesehatan', 'rpk_hipertensi', 'rpk_stroke', 'rpk_jantung', 'rpk_diabetes',
            'rpk_lainnya', 'riwayat_penyakit_dahulu', 'riwayat_operasi', 'alergi_makanan',
            'alergi_obat', 'alergi_debu', 'alergi_lainnya', 'kebiasaan_olahraga', 'kebiasaan_merokok',
            'kebiasaan_alkohol', 'kebiasaan_obat', 'kebiasaan_lainnya', 'hasil_ekg', 'keterangan_ekg', 'catatan',
        ],
        'lab' => [
            'hemoglobin', 'leukosit', 'eritrosit', 'trombosit', 'hematokrit', 'basofil', 'eosinofil',
            'stab', 'segmen', 'limfosit', 'monosit', 'mcv', 'mch', 'mchc', 'gds', 'gda',
            'hbsag', 'hiv', 'kesimpulan',
        ],
        'radiology' => [
            'jenis_pemeriksaan', 'foto_thorax', 'jantung', 'paru_kanan', 'paru_kiri', 'diafragma',
            'tulang', 'kesimpulan',
        ],
        'doctor' => [
            'kulit', 'rambut', 'visus_mata', 'buta_warna', 'lapang_pandang', 'hidung', 'telinga',
            'tonsil', 'faring', 'gigi', 'kelenjar_gondok', 'suara_pernafasan', 'ronchi', 'wheezing',
            'lainnya_paru', 'jantung', 'hati', 'limpa', 'epigastrium', 'ginjal', 'lainnya_abdomen',
            'ekstremitas', 'neurologi', 'diagnosa', 'rekomendasi', 'catatan',
        ],
    ];

    private const MCU_STATION_MAP = [
        'nurse'     => [McuNurseAssessment::class, 'nurseAssessment', 'file_ekg'],
        'lab'       => [McuLabResult::class, 'labResult', 'file_path'],
        'radiology' => [McuRadiologyResult::class, 'radiologyResult', 'file_path'],
        'doctor'    => [McuDoctorExamination::class, 'doctorExamination', null],
    ];

    /** Antrian MCU — semua lamaran status `mcu` lintas lowongan (buat staff klinis HRIS). */
    public function mcuQueue(): JsonResponse
    {
        $apps = JobApplication::where('status', 'mcu')
            ->with(['user:id,name,email', 'jobOpening:id,hris_job_opening_id,title', 'mcuInvitee'])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'data' => $apps->map(fn (JobApplication $a) => [
                'job_application_id' => $a->id,
                'applicant_name'     => $a->user?->name,
                'job_opening_title'  => $a->jobOpening?->title,
                'hris_job_opening_id' => $a->jobOpening?->hris_job_opening_id,
                'stations_filled'    => $this->mcuStationsFilled($a->mcuInvitee),
                'status_mcu'         => $a->mcuInvitee?->status_mcu ?? 'pending',
                'updated_at'         => $a->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /** State MCU 1 lamaran: status + isi tiap stasiun (null kalau belum diisi). */
    public function showMcu(string $jobApplicationId): JsonResponse
    {
        $application = JobApplication::with([
            'mcuInvitee.nurseAssessment', 'mcuInvitee.labResult',
            'mcuInvitee.radiologyResult', 'mcuInvitee.doctorExamination',
        ])->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        $inv = $application->mcuInvitee;

        return response()->json([
            'status'      => $application->status,
            'status_mcu'  => $inv?->status_mcu ?? 'pending',
            'catatan_mcu' => $inv?->catatan_mcu,
            'nurse'       => $inv?->nurseAssessment,
            'lab'         => $inv?->labResult,
            'radiology'   => $inv?->radiologyResult,
            'doctor'      => $inv?->doctorExamination,
        ]);
    }

    /**
     * Simpan 1 stasiun MCU (nurse|lab|radiology|doctor). Body = field-field stasiun itu
     * (lihat MCU_FIELDS) + opsional `file` multipart. McuInvitee (+ McuSchedule dummy)
     * dibuat otomatis kalau belum ada. Status wajib `mcu`.
     */
    public function updateMcuStation(Request $request, string $jobApplicationId, string $station): JsonResponse
    {
        if (! isset(self::MCU_STATION_MAP[$station])) {
            return response()->json(['message' => "Stasiun tidak dikenal: {$station}."], 404);
        }

        $application = JobApplication::find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if ($application->status !== 'mcu') {
            return response()->json(['message' => "MCU cuma bisa diisi saat status 'mcu' (sekarang '{$application->status}')."], 422);
        }

        $rules = [];
        foreach (self::MCU_FIELDS[$station] as $f) {
            $rules[$f] = 'nullable';
        }
        if ($station === 'nurse') {
            foreach (['tinggi_badan', 'berat_badan', 'suhu'] as $f) {
                $rules[$f] = 'required|numeric';
            }
            foreach (['nadi', 'pernafasan'] as $f) {
                $rules[$f] = 'required|integer';
            }
            $rules['tekanan_darah'] = 'required|string';
        }
        $rules['file'] = 'nullable|file|max:5120';
        $data = $request->validate($rules);

        $invitee = $this->resolveMcuInvitee($application);

        $payload = collect($data)->only(self::MCU_FIELDS[$station])->all();

        [$modelClass, , $fileColumn] = self::MCU_STATION_MAP[$station];

        if ($fileColumn && $request->hasFile('file')) {
            $payload[$fileColumn] = $request->file('file')->store("mcu/{$station}/{$invitee->id}", 'local');
        }

        $modelClass::updateOrCreate(['mcu_invitee_id' => $invitee->id], $payload);

        return response()->json(['message' => 'Saved', 'station' => $station]);
    }

    /**
     * Keputusan fit/unfit MCU (HRD). Guard: pemeriksaan dokter sudah ada. fit → `onboarding`,
     * unfit → `rejected` + notif draft.
     */
    public function decideMcu(Request $request, string $jobApplicationId): JsonResponse
    {
        $data = $request->validate([
            'result'  => 'required|in:fit,unfit',
            'catatan' => 'nullable|string',
        ]);

        $application = JobApplication::with('mcuInvitee.doctorExamination')->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if ($application->status !== 'mcu') {
            return response()->json(['message' => "Status sekarang '{$application->status}', bukan 'mcu'."], 422);
        }

        $invitee = $application->mcuInvitee;

        if (! $invitee || ! $invitee->doctorExamDone()) {
            return response()->json(['message' => 'Pemeriksaan dokter belum diisi.'], 422);
        }

        $invitee->update(['status_mcu' => $data['result'], 'catatan_mcu' => $data['catatan'] ?? null]);

        if ($data['result'] === 'fit') {
            $application->update(['status' => 'onboarding']);
            JobOpeningStageLog::record($application->job_opening_id, 'mcu', 'onboarding', 1);
        } else {
            $application->update(['status' => 'rejected']);
            JobOpeningStageLog::record($application->job_opening_id, 'mcu', 'rejected', 1);
            ApplicationNotification::createDraftFor($application, CompanyMessageTemplate::EVENT_MCU_REJECTED);
        }

        return response()->json(['message' => 'Decided', 'status' => $application->status, 'status_mcu' => $data['result']]);
    }

    /** @return list<string> stasiun yang sudah diisi */
    private function mcuStationsFilled(?McuInvitee $invitee): array
    {
        if (! $invitee) {
            return [];
        }

        return collect(self::MCU_STATION_MAP)
            ->filter(fn ($cfg, $station) => $invitee->{$cfg[1]}()->exists())
            ->keys()
            ->all();
    }

    private function resolveMcuInvitee(JobApplication $application): McuInvitee
    {
        if ($application->mcuInvitee) {
            return $application->mcuInvitee;
        }

        $schedule = McuSchedule::create([
            'job_opening_id' => $application->job_opening_id,
            'mcu_date'       => now()->toDateString(),
            'status'         => 'published',
        ]);

        return McuInvitee::create([
            'mcu_schedule_id'    => $schedule->id,
            'job_application_id' => $application->id,
            'status_mcu'         => 'pending',
        ]);
    }

    /**
     * Tandai onboarding selesai (rewrite TtdOnboardingPage): candidate.is_onboarded = true,
     * status → `passed`, kirim OnboardingInviteNotification. Guard status `onboarding`.
     */
    public function completeOnboarding(string $jobApplicationId): JsonResponse
    {
        $application = JobApplication::with(['candidate', 'user'])->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        if ($application->status !== 'onboarding') {
            return response()->json(['message' => "Status sekarang '{$application->status}', bukan 'onboarding'."], 422);
        }

        $application->candidate?->update(['is_onboarded' => true]);
        $application->update(['status' => 'passed']);
        JobOpeningStageLog::record($application->job_opening_id, 'onboarding', 'passed', 1);

        try {
            $application->user?->notify(new \App\Notifications\OnboardingInviteNotification($application));
        } catch (\Throwable $e) {
            // email gagal tidak boleh menggagalkan transisi status
        }

        return response()->json(['message' => 'Completed', 'status' => 'passed']);
    }

    /**
     * Payload lengkap data diri kandidat (candidate + 8 tabel anak) buat ditarik HRIS jadi
     * calon karyawan baru. Cuma tersedia setelah row candidate dibuat (status onboarding/passed,
     * lihat JobApplication::booted()) — selain itu balas 409.
     *
     * Dokumen fisik (KTP/ijazah dll, disk `local` privat) TIDAK di-inline di sini — cuma
     * metadata + file_path. Transfer binernya via endpoint proxy terpisah (belum dibuat,
     * lihat planning/2026-08-28-integrasi-hris-rekrutmen-dua-arah.md).
     */
    public function showCandidate(string $jobApplicationId): JsonResponse
    {
        $application = JobApplication::with('user:id,name,email')->find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        $candidate = $application->candidate()->with([
            'familyMembers', 'educations', 'trainings', 'organizations', 'workExperiences',
            'references', 'additionalInfo', 'selfAssessment', 'documents', 'agreements',
        ])->first();

        if (! $candidate) {
            return response()->json([
                'message' => 'Candidate data not available yet (status: ' . $application->status . '). Row dibuat saat onboarding/passed.',
            ], 409);
        }

        return response()->json([
            'job_application' => [
                'id'     => $application->id,
                'status' => $application->status,
                'email'  => $application->user?->email,
            ],
            'candidate' => $candidate->toArray(),
        ]);
    }

    /**
     * HRIS menggerakkan status lamaran (rewrite aksi-aksi Filament sisi admin ke portal HRIS).
     * Lewat Eloquent `update()` supaya `JobApplication::booted()` jalan: tulis
     * job_application_status_logs + bikin row candidate saat onboarding/passed + trigger email
     * pelamar. `changed_by` di log jadi null (request API pakai token, bukan user) — trade-off
     * yang diterima, aktor sebenarnya tercatat di sisi HRIS.
     */
    public function updateApplicationStatus(Request $request, string $jobApplicationId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:applied,screening,shortlisted,interview,offering,mcu,onboarding,passed,rejected',
        ]);

        $application = JobApplication::find($jobApplicationId);

        if (! $application) {
            return response()->json(['message' => 'Job application not found: ' . $jobApplicationId], 404);
        }

        $from = $application->status;
        $to = $data['status'];

        if ($from === $to) {
            return response()->json(['message' => "Status sudah '{$to}', tidak ada perubahan."], 422);
        }

        $allowed = self::HRIS_TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            return response()->json([
                'message' => "Transisi '{$from}' → '{$to}' tidak diizinkan dari HRIS." .
                    ($allowed ? " Dari '{$from}' hanya boleh: " . implode(', ', $allowed) . '.' : " '{$from}' status terminal."),
            ], 422);
        }

        $candidateExistedBefore = $application->candidate()->exists();

        $application->update(['status' => $to]);

        return response()->json([
            'message'            => 'Updated',
            'job_application_id'  => $application->id,
            'from'               => $from,
            'to'                 => $to,
            'candidate_created'  => ! $candidateExistedBefore && $application->candidate()->exists(),
        ]);
    }

    /**
     * Proxy unduh satu dokumen kandidat dari disk `local` (privat) — biar HRIS bisa impor
     * KTP/ijazah dll tanpa akses langsung ke storage project ini. Cek dokumen memang milik
     * kandidat dari lamaran yang diminta.
     */
    public function downloadCandidateDocument(string $jobApplicationId, string $documentId): StreamedResponse
    {
        $application = JobApplication::with('candidate')->find($jobApplicationId);

        abort_unless($application, 404, 'Job application not found');
        abort_unless($application->candidate, 409, 'Candidate belum ada untuk lamaran ini');

        $document = $application->candidate->documents()->whereKey($documentId)->first();

        abort_unless($document, 404, 'Document not found');
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404, 'File tidak ada di storage');

        return Storage::disk('local')->response($document->file_path);
    }

    public function upsertJobOpening(Request $request, string $hrisJobOpeningId): JsonResponse
    {
        $data = $request->validate([
            'hris_job_opening_id'  => 'required|string',
            'hris_company_code'    => 'required|string',
            'title'                => 'required|string|max:255',
            'quota'                => 'required|integer|min:1',
            'max_applicants'       => 'nullable|integer|min:1',
            'applications_deadline'=> 'nullable|date',
            'description'          => 'nullable|string',
            'requirements'         => 'nullable|string',
            'is_active'            => 'required|boolean',
        ]);

        $company = Company::where('hris_code', $data['hris_company_code'])->first();

        if (!$company) {
            return response()->json([
                'message' => 'Company not found for hris_company_code: ' . $data['hris_company_code'],
            ], 422);
        }

        $jobOpening = JobOpening::firstOrNew(['hris_job_opening_id' => $hrisJobOpeningId]);

        if (!$jobOpening->exists) {
            $jobOpening->id = (string) Str::ulid();
        }

        $jobOpening->fill([
            'company_id'            => $company->id,
            'company_name'          => $company->name,
            'title'                 => $data['title'],
            'quota'                 => $data['quota'],
            'max_applicants'        => $data['max_applicants'],
            'applications_deadline' => $data['applications_deadline'],
            'description'           => $data['description'],
            'requirements'          => $data['requirements'],
            'is_active'             => $data['is_active'],
        ]);

        $jobOpening->save();

        return response()->json([
            'message'     => $jobOpening->wasRecentlyCreated ? 'Created' : 'Updated',
            'id'          => $jobOpening->id,
            'hris_job_opening_id' => $jobOpening->hris_job_opening_id,
        ], $jobOpening->wasRecentlyCreated ? 201 : 200);
    }
}
