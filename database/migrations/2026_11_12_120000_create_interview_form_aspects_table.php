<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Master Form Interview: daftar aspek penilaian wawancara PER PERUSAHAAN (tiap perusahaan boleh beda).
 * Diisi HR dari panel rekrutmen di HRIS. Penilaian yang tersimpan (interview_results) menyalin (snapshot)
 * aspek-aspek ini, jadi mengubah master tidak merusak penilaian lama.
 *
 * Default 9 aspek (form interview baku) langsung dipasang untuk perusahaan dengan kode HRIS 0000, 0001, 0002.
 */
return new class extends Migration
{
    /** Kode HRIS perusahaan yang otomatis mendapat aspek default. */
    private const DEFAULT_COMPANY_CODES = ['0000', '0001', '0002'];

    private const DEFAULT_ASPECTS = [
        ['Komunikasi', 'Bagaimana yang bersangkutan memperkenalkan diri, menyampaikan pemikiran'],
        ['Pengetahuan', 'Sejauh mana pengetahuan tentang bidang yang dilamar'],
        ['Pengalaman', 'Pengalaman kerja / belajar yang meningkatkan kompetensinya'],
        ['Kemampuan Analis', 'Bagaimana ketika mengidentifikasi masalah, melihat data & fakta guna menentukan tindakan selanjutnya'],
        ['Pendidikan', 'Kualifikasi pendidikan yang diraih (IPK)'],
        ['Pengendalian Diri / Emosi', 'Kepercayaan diri dan sikap / attitude saat berkomunikasi dan menghadapi masalah'],
        ['Antusiasme', 'Minat calon untuk bergabung di perusahaan'],
        ['Inisiatif', 'Ditampilkan pada saat wawancara, keinginan menjadi subjek (aktif/pasif)'],
        ['Penampilan', 'Bisa menjadi cerminan kepribadian calon karyawan'],
    ];

    public function up(): void
    {
        Schema::create('interview_form_aspects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'sort_order']);
        });

        $now = now();

        foreach (DB::table('companies')->whereIn('hris_code', self::DEFAULT_COMPANY_CODES)->pluck('id') as $companyId) {
            foreach (self::DEFAULT_ASPECTS as $i => [$name, $description]) {
                DB::table('interview_form_aspects')->insert([
                    'id' => strtolower((string) Str::ulid()),
                    'company_id' => $companyId,
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_form_aspects');
    }
};
