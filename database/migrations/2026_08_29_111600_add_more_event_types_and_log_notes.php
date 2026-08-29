<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_EVENTS = [
        'screening_rejected', 'screening_passed', 'interview_scheduled', 'quiz_sent',
        'quiz_rejected', 'interview_rejected', 'mcu_scheduled', 'mcu_rejected', 'onboarding_invite',
    ];

    private const NEW_EVENTS = [
        ...self::OLD_EVENTS,
        'quiz_passed', 'offering_letter_ready',
    ];

    public function up(): void
    {
        $list = "'" . implode("','", self::NEW_EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");

        Schema::table('job_application_status_logs', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('to_status');
        });
    }

    public function down(): void
    {
        Schema::table('job_application_status_logs', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        $list = "'" . implode("','", self::OLD_EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }
};
