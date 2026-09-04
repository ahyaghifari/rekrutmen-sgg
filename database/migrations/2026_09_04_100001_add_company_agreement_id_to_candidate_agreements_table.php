<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_agreements', function (Blueprint $table) {
            // Kolom lama dipertahankan untuk baris legacy; alur baru pakai company_agreement_id.
            $table->string('agreement_type')->nullable()->change();
            $table->foreignUlid('company_agreement_id')->nullable()->after('agreement_type')
                ->constrained('company_agreements')->cascadeOnDelete();
            $table->unique(['candidate_id', 'company_agreement_id']);
        });
    }

    public function down(): void
    {
        Schema::table('candidate_agreements', function (Blueprint $table) {
            $table->dropUnique(['candidate_id', 'company_agreement_id']);
            $table->dropConstrainedForeignId('company_agreement_id');
        });
    }
};
