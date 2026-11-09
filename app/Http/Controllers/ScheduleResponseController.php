<?php

namespace App\Http\Controllers;

use App\Models\InterviewInvitee;
use App\Models\McuInvitee;
use Illuminate\Http\Request;

// Respons pelamar atas jadwal wawancara / MCU. Hanya satu kali; keputusan reschedule diurus HR di panel admin.
class ScheduleResponseController extends Controller
{
    public function store(Request $request, string $type, string $invitee)
    {
        abort_unless(in_array($type, ['wawancara', 'mcu'], true), 404);
        $model = $type === 'mcu' ? McuInvitee::class : InterviewInvitee::class;
        $dateCol = $type === 'mcu' ? 'mcu_date' : 'interview_date';

        $row = $model::with('schedule', 'jobApplication')->findOrFail($invitee);
        abort_unless($row->jobApplication->user_id === $request->user()->id, 403);

        $schedule = $row->schedule;
        if ($schedule->status !== 'published' || $schedule->$dateCol?->lt(today())) {
            return back()->with('warning', 'Jadwal ini sudah tidak bisa direspons.');
        }

        $data = $request->validate([
            'response'        => ['required', 'in:attend,absent_with_reason,reschedule'],
            'response_reason' => ['required_if:response,absent_with_reason,reschedule', 'nullable', 'string', 'max:500'],
            'proposed_date'   => ['required_if:response,reschedule', 'nullable', 'date', 'after:today'],
            'proposed_time'   => ['required_if:response,reschedule', 'nullable', 'date_format:H:i'],
        ]);

        $isResched = $data['response'] === 'reschedule';
        // Update atomik: klik ganda / dua tab tidak bisa menimpa jawaban pertama.
        $saved = $model::where('id', $row->id)->whereNull('responded_at')->update([
            'response'          => $data['response'],
            'response_reason'   => $data['response'] === 'attend' ? null : $data['response_reason'],
            'proposed_date'     => $isResched ? $data['proposed_date'] : null,
            'proposed_time'     => $isResched ? $data['proposed_time'] : null,
            'reschedule_status' => $isResched ? 'pending' : null,
            'responded_at'      => now(),
        ]);

        return back()->with($saved ? 'success' : 'warning', $saved
            ? 'Jawaban Anda sudah terkirim.'
            : 'Anda sudah memberi jawaban untuk jadwal ini dan tidak dapat mengubahnya.');
    }
}
