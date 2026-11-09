<?php

namespace App\Models\Concerns;

use App\Models\ApplicationNotification;

/**
 * Untuk InterviewInvitee / McuInvitee: begitu HR mengubah reschedule_status dari pending ke approved/rejected
 * (lewat model, mis. aksi Filament), email keputusan otomatis dikirim ke pelamar.
 */
trait NotifiesRescheduleDecision
{
    public static function bootNotifiesRescheduleDecision(): void
    {
        static::updated(function (self $invitee) {
            if (! $invitee->wasChanged('reschedule_status') || $invitee->getOriginal('reschedule_status') !== 'pending'
                || ! in_array($invitee->reschedule_status, ['approved', 'rejected'], true)) {
                return;
            }

            $invitee->loadMissing('schedule', 'jobApplication');
            $isMcu = $invitee instanceof \App\Models\McuInvitee;
            $s = $invitee->schedule;
            $approved = $invitee->reschedule_status === 'approved';

            // Disetujui -> jadwal usulan pelamar; ditolak -> jadwal awal tetap berlaku.
            $date = $approved ? $invitee->proposed_date : ($isMcu ? $s->mcu_date : $s->interview_date);
            $time = $approved ? $invitee->proposed_time : ($isMcu ? $s->mcu_time : $s->interview_time);

            ApplicationNotification::createAndSend(
                $invitee->jobApplication,
                ($isMcu ? 'mcu' : 'interview') . '_reschedule_' . $invitee->reschedule_status,
                [
                    'interview_date' => $date?->translatedFormat('l, j F Y') ?? '-',
                    'interview_time' => $time ? substr($time, 0, 5) : null,
                    'location'       => $s->location,
                    'notes'          => $invitee->reschedule_note,
                ],
            );
        });
    }
}
