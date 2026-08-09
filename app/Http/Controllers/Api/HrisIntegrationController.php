<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobOpening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HrisIntegrationController extends Controller
{
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

        if ($company->management_mode === 'free') {
            return response()->json([
                'message' => 'Company is managed manually (free mode). HRIS sync rejected.',
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
