<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('offering_letter_generated_at');

            $table->string('offering_letter_path')->nullable()->after('interview_result');
            $table->timestamp('offering_letter_uploaded_at')->nullable()->after('offering_letter_path');
            $table->string('offering_letter_signed_path')->nullable()->after('offering_letter_uploaded_at');
            $table->timestamp('offering_letter_signed_at')->nullable()->after('offering_letter_signed_path');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'offering_letter_path',
                'offering_letter_uploaded_at',
                'offering_letter_signed_path',
                'offering_letter_signed_at',
            ]);

            $table->timestamp('offering_letter_generated_at')->nullable()->after('interview_result');
        });
    }
};
