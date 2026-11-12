<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Keputusan reschedule baru "approved_set" (Setuju Ditetapkan): HR menyetujui permintaan reschedule
 * pelamar tapi menetapkan sendiri jadwal barunya. Dipakai wawancara & MCU.
 */
return new class extends Migration
{
    private array $tables = ['interview_invitees', 'mcu_invitees'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN reschedule_status ENUM('pending','approved','approved_set','rejected') NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::table($table)->where('reschedule_status', 'approved_set')->update(['reschedule_status' => 'approved']);
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN reschedule_status ENUM('pending','approved','rejected') NULL");
        }
    }
};
