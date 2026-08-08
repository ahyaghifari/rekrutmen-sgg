<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    public function index()
    {
        $applications = Auth::user()->jobApplications()->with('jobOpening')->latest()->get();
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

        $rules = [];
        foreach ($jobOpening->company->formSections as $section) {
            foreach ($section->fields as $field) {
                $base = $field->is_required ? 'required' : 'nullable';
                $opts = $field->options ?? [];

                if ($field->type === 'file') {
                    $r = [$base, 'file'];
                    if (!empty($opts['mimes']))  $r[] = 'mimes:' . implode(',', $opts['mimes']);
                    if (!empty($opts['max_kb'])) $r[] = 'max:' . $opts['max_kb'];
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

        $application = JobApplication::create([
            'user_id'        => Auth::id(),
            'job_opening_id' => $jobOpening->id,
            'status'         => JobApplication::STATUS_APPLIED,
        ]);

        $answers = [];
        foreach ($request->input('fields', []) as $key => $value) {
            $answers[] = ['field_key' => $key, 'value' => $value];
        }
        foreach ($jobOpening->company->formSections as $section) {
            foreach ($section->fields->where('type', 'file') as $field) {
                if ($request->hasFile("files.{$field->name}")) {
                    $path = $request->file("files.{$field->name}")
                        ->store("applications/{$field->name}", 'public');
                    $answers[] = ['field_key' => $field->name, 'value' => $path];
                }
            }
        }

        $application->answers()->createMany($answers);

        return redirect()->route('dashboard')
            ->with('success', 'Lamaran berhasil dikirim! Tim HR kami akan meninjau berkas Anda.');
    }
}
