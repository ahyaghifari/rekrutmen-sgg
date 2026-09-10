<?php

namespace App\Http\Controllers;

use App\Models\ApplicationNotification;
use App\Models\CompanyMessageTemplate;
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

    /**
     * Kandidat: upload surat yang sudah ditandatangani. Boleh sekali; unggah lagi hanya
     * kalau tim rekrutmen minta kirim ulang (offering_signed_revision_reason terisi).
     */
    public function uploadSigned(Request $request): RedirectResponse
    {
        $application = $this->currentOfferingApplication();

        if ($application->offering_letter_signed_path && $application->offering_signed_revision_reason === null) {
            return back()->with('warning', 'Surat sudah diunggah dan sedang dikonfirmasi tim rekrutmen. Kamu bisa mengunggah ulang hanya jika diminta.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $path = $request->file('file')->store("offering-letters/{$application->id}/signed", 'local');

        $application->update([
            'offering_letter_signed_path'     => $path,
            'offering_letter_signed_at'       => now(),
            'offering_signed_revision_reason' => null,
            'offering_signed_reviewed_at'     => null,
        ]);

        // Purge supaya email konfirmasi bisa dikirim ulang di siklus review berikutnya
        // (createDraftFor mengembalikan null bila row event sudah ada).
        ApplicationNotification::where('job_application_id', $application->id)
            ->whereIn('event', [
                CompanyMessageTemplate::EVENT_OFFERING_SIGNED_RECEIVED,
                CompanyMessageTemplate::EVENT_OFFERING_SIGNED_ACCEPTED,
            ])
            ->delete();
        ApplicationNotification::createAndSend($application, CompanyMessageTemplate::EVENT_OFFERING_SIGNED_RECEIVED);

        return back()->with('success', 'Surat penawaran bertanda tangan berhasil diupload. Tim rekrutmen akan mengonfirmasi pada jam kerja.');
    }

    /** Kandidat: ajukan negosiasi sebelum menandatangani. */
    public function negotiate(Request $request): RedirectResponse
    {
        $application = $this->currentOfferingApplication();

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Tidak boleh kirim lagi selama pengajuan terakhir belum dibalas tim rekrutmen.
        if ($application->offeringNegotiations()->latest('created_at')->first()?->sender === 'applicant') {
            return back()->with('warning', 'Pengajuan negosiasi kamu masih menunggu balasan tim rekrutmen.');
        }

        $application->offeringNegotiations()->create([
            'sender'  => 'applicant',
            'message' => $data['message'],
        ]);

        return back()->with('success', 'Pengajuan negosiasi terkirim. Tim rekrutmen akan meninjau.');
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
