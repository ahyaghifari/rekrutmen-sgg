<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JobApplicationController extends Controller
{
    public function index()
    {
        $applications = Auth::user()->jobApplications()
            ->with(['jobOpening', 'jobOpening.quizzes', 'quizAttempts'])
            ->latest()->get();
        return view('lamaran.index', compact('applications'));
    }

    public function show(JobApplication $application)
    {
        abort_if($application->user_id !== Auth::id(), 403);
        $application->load(['jobOpening.company.formSections.fields', 'answers']);
        return view('lamaran.show', compact('application'));
    }

    public function create(JobOpening $jobOpening)
    {
        abort_if(! $jobOpening->is_active, 404);

        $jobOpening->load('company.formSections.fields');
        abort_if(! ($jobOpening->company?->is_active ?? false), 404);

        if (JobApplication::hasActiveApplication(Auth::id())) {
            return redirect()->route('dashboard')
                ->with('warning', 'Kamu sudah memiliki lamaran aktif. Selesaikan atau tunggu hasilnya sebelum melamar posisi lain.');
        }

        $previousApp = Auth::user()->jobApplications()->with(['jobOpening', 'answers'])->latest()->first();

        $co    = $jobOpening->company;
        $brand = $co?->brand_color ?? '#042c6c';
        [$r, $g, $b] = sscanf($brand, '#%02x%02x%02x');
        $brandDark = sprintf('#%02x%02x%02x', intval($r * 0.65), intval($g * 0.65), intval($b * 0.65));

        return view('lamaran.create', compact('jobOpening', 'previousApp', 'co', 'brand', 'brandDark', 'r', 'g', 'b'));
    }

    public function store(Request $request, JobOpening $jobOpening)
    {
        abort_if(! $jobOpening->is_active, 404);

        $jobOpening->load('company.formSections.fields');
        abort_if(! ($jobOpening->company?->is_active ?? false), 404);

        if (JobApplication::hasActiveApplication(Auth::id())) {
            return redirect()->route('dashboard')
                ->with('warning', 'Kamu sudah memiliki lamaran aktif.');
        }

        // Data Utama: section tetap paling atas, wajib di semua lowongan. Field master bernama sama diabaikan.
        $rules = [
            'fields.full_name'   => 'required|string|max:255',
            'fields.birth_place' => 'required|string|max:100',
            'fields.birth_date'  => 'required|date|before:today',
            'fields.gender'      => 'required|in:Laki-laki,Perempuan',
            'fields.domicile'    => 'required|string|max:255',
            'fields.education_level' => 'required|in:SMA/SMK,D-III,D-IV,S-I,S-II,Profesi',
        ];
        foreach ($jobOpening->company->formSections as $section) {
            foreach ($section->fields as $field) {
                if (in_array($field->name, ['full_name', 'birth_place', 'birth_date', 'gender', 'domicile', 'education_level'])) {
                    continue;
                }
                $base = $field->is_required ? 'required' : 'nullable';
                $opts = $field->options ?? [];

                if ($field->type === 'file') {
                    $r = [$base, 'file'];
                    $r[] = 'mimes:' . implode(',', $opts['mimes'] ?? ['pdf', 'jpg', 'jpeg', 'png']);
                    $r[] = 'max:' . ($opts['max_kb'] ?? 5120);
                    $rules["files.{$field->name}"] = implode('|', $r);
                } elseif ($field->type === 'number') {
                    $rules["fields.{$field->name}"] = "{$base}|numeric";
                } elseif ($field->type === 'date') {
                    $rules["fields.{$field->name}"] = "{$base}|date";
                } elseif (in_array($field->type, ['select', 'radio']) && !empty($opts['choices'])) {
                    $rules["fields.{$field->name}"] = "{$base}|in:" . implode(',', $opts['choices']);
                } else {
                    $rules["fields.{$field->name}"] = "{$base}|string|max:2000";
                }
            }
        }

        $request->validate($rules);

        $answers = [];
        foreach ($request->input('fields', []) as $key => $value) {
            $answers[] = ['field_key' => $key, 'value' => $value];
        }
        foreach ($jobOpening->company->formSections as $section) {
            foreach ($section->fields->where('type', 'file') as $field) {
                if ($request->hasFile("files.{$field->name}")) {
                    $path = $request->file("files.{$field->name}")
                        ->store("applications/{$field->name}", 'local');
                    $answers[] = ['field_key' => $field->name, 'value' => $path];
                }
            }
        }

        // Lock user row biar dua request bersamaan gak lolos cek "1 lamaran aktif" bareng-bareng (race condition).
        $duplicate = DB::transaction(function () use ($jobOpening, $answers) {
            User::whereKey(Auth::id())->lockForUpdate()->first();

            if (JobApplication::hasActiveApplication(Auth::id())) {
                return true;
            }

            $application = JobApplication::create([
                'user_id'        => Auth::id(),
                'job_opening_id' => $jobOpening->id,
                'status'         => JobApplication::STATUS_APPLIED,
            ]);

            $application->answers()->createMany($answers);

            return false;
        });

        if ($duplicate) {
            return redirect()->route('dashboard')
                ->with('warning', 'Kamu sudah memiliki lamaran aktif.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Lamaran berhasil dikirim! Tim HR kami akan meninjau berkas Anda.');
    }

    /**
     * Serve berkas lamaran (CV, KTP, dll) — privat, hanya pemilik lamaran atau staff yang boleh akses.
     */
    public function downloadAttachment(JobApplication $application, string $field)
    {
        $user = Auth::user();
        $isOwner = $user->id === $application->user_id;
        $isSuperAdmin = $user->hasAnyRole(['super_admin', 'Super Admin']);
        $isCompanyStaff = $user->is_staff && $user->company_id === $application->jobOpening->company_id;

        abort_unless($isOwner || $isSuperAdmin || $isCompanyStaff, 403);

        $answer = $application->answers()->where('field_key', $field)->firstOrFail();

        abort_unless(Storage::disk('local')->exists($answer->value), 404);

        return Storage::disk('local')->response($answer->value);
    }
}
