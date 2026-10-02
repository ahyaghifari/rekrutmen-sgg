<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pisah kompensasi mode LIST (RS) ke tabel sendiri. Sebelumnya numpang di
     * job_opening_offering_compensation_rows lewat kolom `kind` — sekarang rows = khusus mode
     * TABEL (SGG), items = khusus mode LIST (RS, type mitra/kontrak, satu kolom nominal).
     */
    public function up(): void
    {
        Schema::create('job_opening_offering_compensation_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->string('type'); // mitra | kontrak
            $table->string('label');
            $table->string('amount')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['job_opening_id', 'type']);
        });

        DB::table('job_opening_offering_compensation_rows')
            ->whereIn('kind', ['mitra', 'kontrak'])
            ->orderBy('sort_order')
            ->get()
            ->each(fn ($r) => DB::table('job_opening_offering_compensation_items')->insert([
                'id' => $r->id,
                'job_opening_id' => $r->job_opening_id,
                'type' => $r->kind,
                'label' => $r->label,
                'amount' => $r->kind === 'mitra' ? $r->amount_orientasi : $r->amount_kontrak,
                'sort_order' => $r->sort_order,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]));

        DB::table('job_opening_offering_compensation_rows')->whereIn('kind', ['mitra', 'kontrak'])->delete();

        Schema::table('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }

    public function down(): void
    {
        Schema::table('job_opening_offering_compensation_rows', function (Blueprint $table) {
            $table->string('kind')->nullable()->after('label');
        });

        DB::table('job_opening_offering_compensation_items')->get()->each(fn ($i) => DB::table('job_opening_offering_compensation_rows')->insert([
            'id' => $i->id,
            'job_opening_id' => $i->job_opening_id,
            'label' => $i->label,
            'kind' => $i->type,
            'amount_orientasi' => $i->type === 'mitra' ? $i->amount : null,
            'amount_kontrak' => $i->type === 'kontrak' ? $i->amount : null,
            'sort_order' => $i->sort_order,
            'created_at' => $i->created_at,
            'updated_at' => $i->updated_at,
        ]));

        Schema::dropIfExists('job_opening_offering_compensation_items');
    }
};
