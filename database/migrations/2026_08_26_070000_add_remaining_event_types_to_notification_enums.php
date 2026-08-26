<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const EVENTS = [
        'screening_rejected',
        'screening_passed',
        'interview_scheduled',
        'quiz_sent',
        'quiz_rejected',
        'interview_rejected',
        'mcu_scheduled',
        'mcu_rejected',
        'onboarding_invite',
    ];

    public function up(): void
    {
        $list = "'" . implode("','", self::EVENTS) . "'";
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM({$list}) NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM({$list}) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE company_message_templates MODIFY COLUMN event ENUM('screening_rejected','screening_passed','interview_scheduled') NOT NULL");
        DB::statement("ALTER TABLE application_notifications MODIFY COLUMN event ENUM('screening_rejected','screening_passed','interview_scheduled') NOT NULL");
    }
};
