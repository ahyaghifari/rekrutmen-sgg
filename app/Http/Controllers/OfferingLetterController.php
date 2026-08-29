<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OfferingLetterController extends Controller
{
    /** Admin: lihat/unduh surat penawaran (dari recruiter) atau surat yang sudah ditandatangani (dari kandidat). */
    public function download(JobApplication $application, string $type)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasAnyRole(['super_admin', 'Super Admin']);
        $isCompanyStaff = $user->is_staff && $user->company_id === $application->jobOpening->company_id;
        abort_unless($isSuperAdmin || $isCompanyStaff, 403);

        $path = $type === 'signed' ? $application->offering_letter_signed_path : $application->offering_letter_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    /** Kandidat: unduh surat penawaran yang diupload recruiter. */
    public function downloadForCandidate(): mixed
    {
        $application = $this->currentOfferingApplication();

        abort_unless($application->offering_letter_path && Storage::disk('local')->exists($application->offering_letter_path), 404);

        return Storage::disk('local')->response($application->offering_letter_path);
    }

    /** Kandidat: upload surat yang sudah ditandatangani. */
    public function uploadSigned(Request $request): RedirectResponse
    {
        $application = $this->currentOfferingApplication();

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $path = $request->file('file')->store("offering-letters/{$application->id}/signed", 'local');

        $application->update([
            'offering_letter_signed_path' => $path,
            'offering_letter_signed_at'   => now(),
        ]);

        return back()->with('success', 'Surat penawaran bertanda tangan berhasil diupload.');
    }

    private function currentOfferingApplication(): JobApplication
    {
        $application = Auth::user()->jobApplications()
            ->where('status', 'offering')
            ->latest()
            ->first();

        abort_unless($application, 403, 'Tidak ada offering letter yang perlu diproses saat ini.');

        return $application;
    }
}
