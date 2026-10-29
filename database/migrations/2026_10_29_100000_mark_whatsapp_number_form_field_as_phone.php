<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Field "whatsapp_number" ditandai options.is_phone supaya panel rekrutmen HRIS menampilkan tombol
// WhatsApp di samping nama kandidat. Cuma data, tanpa ubah skema.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('form_fields')
            ->where('name', 'whatsapp_number')->whereNull('options')
            ->update(['options' => json_encode(['is_phone' => true])]);
    }

    public function down(): void
    {
        DB::table('form_fields')
            ->where('name', 'whatsapp_number')->where('options', json_encode(['is_phone' => true]))
            ->update(['options' => null]);
    }
};
