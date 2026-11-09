<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Tambah event email keputusan reschedule (disetujui / ditolak) untuk wawancara dan MCU. */
return new class extends Migration
{
    private const NEW_EVENTS = [
        'interview_reschedule_approved', 'interview_reschedule_rejected',
        'mcu_reschedule_approved', 'mcu_reschedule_rejected',
    ];

    public function up(): void
    {
        $this->setEnum(array_unique([...$this->currentEvents(), ...self::NEW_EVENTS]));
    }

    public function down(): void
    {
        $in = "'" . implode("','", self::NEW_EVENTS) . "'";
        DB::statement("DELETE FROM application_notifications WHERE event IN ({$in})");
        DB::statement("DELETE FROM company_message_templates WHERE event IN ({$in})");
        $this->setEnum(array_values(array_diff($this->currentEvents(), self::NEW_EVENTS)));
    }

    // Daftar enum dibaca dari DB (bukan ditulis ulang) supaya tidak menghapus event yang ditambah migrasi lain.
    private function currentEvents(): array
    {
        $type = DB::select("SHOW COLUMNS FROM application_notifications LIKE 'event'")[0]->Type;
        preg_match_all("/'([^']+)'/", $type, $m);

        return $m[1];
    }

    private function setEnum(array $events): void
    {
        $list = "'" . implode("','", $events) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }
};
