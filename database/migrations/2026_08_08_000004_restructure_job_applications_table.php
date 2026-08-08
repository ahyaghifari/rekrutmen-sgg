<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'full_name', 'gender', 'whatsapp_number', 'age',
                'domicile_address', 'birth_place', 'birth_date',
                'instagram_handle', 'linkedin_url',
                'education_level', 'major', 'school_name', 'graduation_year',
                'str_valid_until', 'other_position_note', 'work_experience_note',
                'willing_relocate', 'last_salary', 'expected_salary', 'availability',
                'certificate_file_path', 'cv_file_path', 'extra_data',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->enum('gender', ['laki_laki', 'perempuan'])->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->text('domicile_address')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->enum('education_level', ['sma_smk', 'd3', 'd4', 's1', 's2', 'profesi'])->nullable();
            $table->string('major')->nullable();
            $table->string('school_name')->nullable();
            $table->year('graduation_year')->nullable();
            $table->date('str_valid_until')->nullable();
            $table->string('other_position_note')->nullable();
            $table->text('work_experience_note')->nullable();
            $table->boolean('willing_relocate')->default(false);
            $table->decimal('last_salary', 12, 2)->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->enum('availability', ['immediately', '2_weeks', '1_month', 'more_than_2_months'])->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->string('cv_file_path')->nullable();
            $table->json('extra_data')->nullable();
        });
    }
};
