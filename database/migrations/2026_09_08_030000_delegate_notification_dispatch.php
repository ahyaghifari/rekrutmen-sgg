<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengiriman email pemberitahuan pelamar dipindah sepenuhnya ke project ini (HRIS cuma
 * menulis baris). Perubahan tabel `application_notifications`:
 *   - status: tambah 'queued' (siap dikirim; 'draft' = ditahan, 'sent' = selesai)
 *   - event: tambah interview_rescheduled / mcu_rescheduled / offering_negotiation_replied
 *     (dulu email transient, kini disimpan sebagai baris — boleh berulang)
 *   - lepas unique (job_application_id, event) — event di atas bisa berulang; dedup event
 *     bertahap tetap dijaga di level aplikasi (createDraftFor::exists()). FK job_application_id
 *     butuh index sendiri sebelum unique gabungan dilepas.
 */
return new class extends Migration
{
    private const EVENTS = [
        'screening_rejected', 'screening_passed', 'interview_scheduled', 'quiz_sent',
        'quiz_passed', 'quiz_rejected', 'interview_rejected', 'mcu_scheduled', 'mcu_rejected',
        'onboarding_invite', 'offering_letter_ready', 'mcu_passed', 'manual_rejected',
        'interview_rescheduled', 'mcu_rescheduled', 'offering_negotiation_replied',
    ];

    private const OLD_EVENTS = [
        'screening_rejected', 'screening_passed', 'interview_scheduled', 'quiz_sent',
        'quiz_passed', 'quiz_rejected', 'interview_rejected', 'mcu_scheduled', 'mcu_rejected',
        'onboarding_invite', 'offering_letter_ready', 'mcu_passed', 'manual_rejected',
    ];

    private const FK_INDEX = 'application_notifications_job_application_id_index';
    private const UNIQUE_INDEX = 'application_notifications_job_application_id_event_unique';

    public function up(): void
    {
        $list = "'" . implode("','", self::EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN status ENUM('draft','queued','sent') NOT NULL DEFAULT 'draft'");

        Schema::table('application_notifications', function (Blueprint $table) {
            if (! $this->indexExists(self::FK_INDEX)) {
                $table->index('job_application_id', self::FK_INDEX);
            }
        });

        if ($this->indexExists(self::UNIQUE_INDEX)) {
            Schema::table('application_notifications', function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        DB::statement("DELETE FROM application_notifications WHERE status = 'queued' OR event IN ('interview_rescheduled','mcu_rescheduled','offering_negotiation_replied')");

        if (! $this->indexExists(self::UNIQUE_INDEX)) {
            Schema::table('application_notifications', function (Blueprint $table) {
                $table->unique(['job_application_id', 'event']);
            });
        }

        if ($this->indexExists(self::FK_INDEX)) {
            Schema::table('application_notifications', function (Blueprint $table) {
                $table->dropIndex(self::FK_INDEX);
            });
        }

        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN status ENUM('draft','sent') NOT NULL DEFAULT 'draft'");
        $list = "'" . implode("','", self::OLD_EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }

    private function indexExists(string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'application_notifications')
            ->where('index_name', $index)
            ->exists();
    }
};
