<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah 3 event notifikasi untuk loop review offering letter:
 * - offering_signed_received  : otomatis saat pelamar unggah surat bertanda tangan.
 * - offering_signed_accepted  : recruiter menerima surat.
 * - offering_revision_requested: recruiter minta pelamar kirim ulang (memuat alasan).
 */
return new class extends Migration
{
    private const OLD_EVENTS = [
        'screening_rejected', 'screening_passed', 'interview_scheduled', 'quiz_sent',
        'quiz_passed', 'quiz_rejected', 'interview_rejected', 'mcu_scheduled', 'mcu_rejected',
        'onboarding_invite', 'offering_letter_ready', 'mcu_passed', 'manual_rejected',
        'interview_rescheduled', 'mcu_rescheduled', 'offering_negotiation_replied', 'interview_passed',
    ];

    private const NEW_EVENTS = [
        ...self::OLD_EVENTS,
        'offering_signed_received', 'offering_signed_accepted', 'offering_revision_requested',
    ];

    public function up(): void
    {
        $this->setEnum(self::NEW_EVENTS);
    }

    public function down(): void
    {
        DB::statement("DELETE FROM application_notifications WHERE event IN ('offering_signed_received','offering_signed_accepted','offering_revision_requested')");
        $this->setEnum(self::OLD_EVENTS);
    }

    private function setEnum(array $events): void
    {
        $list = "'" . implode("','", $events) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }
};
