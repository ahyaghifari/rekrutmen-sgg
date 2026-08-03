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
        $application->load('jobOpening');
        return view('lamaran.show', compact('application'));
    }

    public function create(JobOpening $jobOpening)
    {
        abort_if(! $jobOpening->is_active, 404);

        if (JobApplication::hasActiveApplication(Auth::id())) {
            return redirect()->route('dashboard')
                ->with('warning', 'Kamu sudah memiliki lamaran aktif. Selesaikan atau tunggu hasilnya sebelum melamar posisi lain.');
        }

        $previousApp = Auth::user()->jobApplications()->latest()->first();

        return view('lamaran.create', compact('jobOpening', 'previousApp'));
    }

    public function store(Request $request, JobOpening $jobOpening)
    {
        abort_if(! $jobOpening->is_active, 404);

        if (JobApplication::hasActiveApplication(Auth::id())) {
            return redirect()->route('dashboard')
                ->with('warning', 'Kamu sudah memiliki lamaran aktif.');
        }

        $data = $request->validate([
            'full_name'            => 'required|string|max:255',
            'gender'               => 'required|in:laki_laki,perempuan',
            'whatsapp_number'      => 'required|string|max:20',
            'domicile_address'     => 'required|string',
            'birth_place'          => 'nullable|string|max:100',
            'birth_date'           => 'required|date|before:today',
            'instagram_handle'     => 'required|string|max:100',
            'linkedin_url'         => 'nullable|url|max:255',
            'education_level'      => 'required|in:sma_smk,d3,d4,s1,s2,profesi',
            'major'                => 'required|string|max:100',
            'school_name'          => 'required|string|max:255',
            'graduation_year'      => 'required|integer|min:1970|max:' . date('Y'),
            'str_valid_until'      => 'nullable|date',
            'other_position_note'  => 'nullable|string|max:255',
            'work_experience_note' => 'nullable|string',
            'willing_relocate'     => 'required|boolean',
            'last_salary'          => 'required|numeric|min:0',
            'expected_salary'      => 'required|numeric|min:0',
            'availability'         => 'required|in:immediately,2_weeks,1_month,more_than_2_months',
            'cv_file'              => 'required|file|mimes:pdf|max:2048',
            'certificate_file'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $cvPath = $request->file('cv_file')->store('applications/cv', 'public');

        $certPath = null;
        if ($request->hasFile('certificate_file')) {
            $certPath = $request->file('certificate_file')->store('applications/certificates', 'public');
        }

        JobApplication::create([
            'user_id'              => Auth::id(),
            'job_opening_id'       => $jobOpening->id,
            'full_name'            => $data['full_name'],
            'gender'               => $data['gender'],
            'whatsapp_number'      => $data['whatsapp_number'],
            'domicile_address'     => $data['domicile_address'],
            'birth_place'          => $data['birth_place'],
            'birth_date'           => $data['birth_date'],
            'instagram_handle'     => $data['instagram_handle'],
            'linkedin_url'         => $data['linkedin_url'],
            'education_level'      => $data['education_level'],
            'major'                => $data['major'],
            'school_name'          => $data['school_name'],
            'graduation_year'      => $data['graduation_year'],
            'str_valid_until'      => $data['str_valid_until'],
            'other_position_note'  => $data['other_position_note'],
            'work_experience_note' => $data['work_experience_note'],
            'willing_relocate'     => $data['willing_relocate'],
            'last_salary'          => $data['last_salary'],
            'expected_salary'      => $data['expected_salary'],
            'availability'         => $data['availability'],
            'cv_file_path'         => $cvPath,
            'certificate_file_path' => $certPath,
            'status'               => JobApplication::STATUS_APPLIED,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Lamaran berhasil dikirim! Tim HR kami akan meninjau berkas Anda.');
    }
}
