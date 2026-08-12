<?php

namespace App\Http\Controllers;

use App\Models\CandidateDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CandidateProfileController extends Controller
{
    private function currentCandidate()
    {
        $candidate = Auth::user()->candidate;
        abort_if(! $candidate, 403, 'Kamu belum lolos seleksi berkas, belum bisa mengisi data diri kandidat.');

        return $candidate;
    }

    public function edit(): View
    {
        $candidate = $this->currentCandidate()->load([
            'familyMembers', 'educations', 'trainings', 'organizations',
            'workExperiences', 'references', 'additionalInfo', 'selfAssessment',
            'documents', 'agreements',
        ]);

        return view('candidate-profile.edit', compact('candidate'));
    }

    public function update(Request $request): RedirectResponse
    {
        $candidate = $this->currentCandidate();

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'nickname'              => ['nullable', 'string', 'max:100'],
            'nik'                   => ['nullable', 'string', 'max:16'],
            'no_kk'                 => ['nullable', 'string', 'max:16'],
            'blood_type'            => ['nullable', 'string', 'max:5'],
            'body_height_weight'    => ['nullable', 'string', 'max:100'],
            'religion'              => ['nullable', 'string', 'max:100'],
            'nationality'           => ['nullable', 'string', 'max:100'],
            'status_marital'        => ['nullable', 'string', 'max:50'],
            'marriage_date'         => ['nullable', 'date'],
            'mother_name'           => ['nullable', 'string', 'max:255'],
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

            'training'            => ['array'],
            'training.*.type'        => ['nullable', 'string', 'max:255'],
            'training.*.institution' => ['nullable', 'string', 'max:255'],
            'training.*.material'    => ['nullable', 'string', 'max:255'],
            'training.*.duration'    => ['nullable', 'string', 'max:100'],
            'training.*.location'    => ['nullable', 'string', 'max:255'],

            'organization'                    => ['array'],
            'organization.*.organization_name' => ['nullable', 'string', 'max:255'],
            'organization.*.field'             => ['nullable', 'string', 'max:255'],
            'organization.*.position'          => ['nullable', 'string', 'max:255'],
            'organization.*.duration'          => ['nullable', 'string', 'max:100'],
            'organization.*.location'          => ['nullable', 'string', 'max:255'],

            'work'                    => ['array'],
            'work.*.company_name'    => ['nullable', 'string', 'max:255'],
            'work.*.business_type'   => ['nullable', 'string', 'max:255'],
            'work.*.company_address' => ['nullable', 'string'],
            'work.*.position'        => ['nullable', 'string', 'max:255'],
            'work.*.start_date'      => ['nullable', 'date'],
            'work.*.end_date'        => ['nullable', 'date'],
            'work.*.salary'          => ['nullable', 'numeric'],
            'work.*.resign_reason'   => ['nullable', 'string'],
            'work.*.is_current'      => ['nullable', 'boolean'],

            'reference'            => ['array'],
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

            'agreements' => ['array'],
        ]);

        DB::transaction(function () use ($candidate, $data, $request) {
            $candidate->update(collect($data)->only([
                'name', 'nickname', 'nik', 'no_kk', 'blood_type', 'body_height_weight',
                'religion', 'nationality', 'status_marital', 'marriage_date', 'mother_name',
                'ktp_address', 'village', 'district', 'city', 'province', 'social_media_accounts',
                'no_bpjskes', 'faskes', 'no_bpjstk', 'company_bpjstk', 'bsi_bank_number',
            ])->all());

            // Replace-all untuk tiap koleksi berulang — sederhana, tidak perlu diffing.
            $candidate->familyMembers()->delete();
            $candidate->familyMembers()->createMany(array_values($data['family'] ?? []));

            $candidate->educations()->delete();
            $candidate->educations()->createMany(array_values($data['education'] ?? []));

            $candidate->trainings()->delete();
            $candidate->trainings()->createMany(array_values($data['training'] ?? []));

            $candidate->organizations()->delete();
            $candidate->organizations()->createMany(array_values($data['organization'] ?? []));

            $candidate->workExperiences()->delete();
            $candidate->workExperiences()->createMany(array_values($data['work'] ?? []));

            $candidate->references()->delete();
            $candidate->references()->createMany(array_values($data['reference'] ?? []));

            if (! empty($data['additional_info'])) {
                $candidate->additionalInfo()->updateOrCreate([], $data['additional_info']);
            }

            if (! empty($data['self_assessment'])) {
                $candidate->selfAssessment()->updateOrCreate([], $data['self_assessment']);
            }

            foreach ($request->file('documents', []) as $i => $doc) {
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

            foreach (['kontrak', 'peraturan', 'tata_tertib'] as $type) {
                if ($request->boolean("agreements.{$type}")) {
                    $candidate->agreements()->updateOrCreate(
                        ['agreement_type' => $type],
                        ['agreed_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
                    );
                }
            }
        });

        return redirect()->route('data-diri.edit')->with('success', 'Data diri berhasil disimpan.');
    }

    public function downloadDocument(CandidateDocument $document)
    {
        $candidate = $this->currentCandidate();
        abort_unless($document->candidate_id === $candidate->id, 403);

        return Storage::disk('local')->response($document->file_path);
    }
}
