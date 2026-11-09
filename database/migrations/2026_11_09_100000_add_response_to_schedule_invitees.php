<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Respons pelamar terhadap jadwal wawancara / MCU (satu kali; keputusan reschedule oleh HR di panel admin).
return new class extends Migration
{
    private array $tables = ['interview_invitees', 'mcu_invitees'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->enum('response', ['attend', 'absent_with_reason', 'reschedule'])->nullable();
                $table->text('response_reason')->nullable();
                $table->date('proposed_date')->nullable();
                $table->time('proposed_time')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->enum('reschedule_status', ['pending', 'approved', 'rejected'])->nullable();
                $table->string('reschedule_decided_by', 26)->nullable(); // ULID user HRIS
                $table->timestamp('reschedule_decided_at')->nullable();
                $table->text('reschedule_note')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['response', 'response_reason', 'proposed_date', 'proposed_time', 'responded_at',
                    'reschedule_status', 'reschedule_decided_by', 'reschedule_decided_at', 'reschedule_note']);
            });
        }
    }
};
