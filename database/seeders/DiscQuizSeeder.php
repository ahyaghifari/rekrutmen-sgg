<?php

namespace Database\Seeders;

use App\Models\Quiz;
use Illuminate\Database\Seeder;

/**
 * Kuis DISC (planning/psikotes-3.jpeg + contoh Google Form). Struktur sama dengan kuis lain:
 * tiap nomor = 1 subtest berisi 2 soal pilihan tunggal (MOST dan LEAST) dengan 4 kalimat yang sama.
 * Tanpa kunci; dihitung HRIS dari type `disc`. Jalankan: php artisan db:seed --class=DiscQuizSeeder
 */
class DiscQuizSeeder extends Seeder
{
    // ponytail: baru nomor yang terlihat di gambar (1-4, 9-12, 17-20); nomor lain ditambah lewat Soal Kuis > Kelola Soal.
    private const ITEMS = [
        1  => ['Gampangan, Mudah setuju', 'Percaya, Mudah percaya pada orang', 'Petualang, Mengambil resiko', 'Toleran, Menghormati'],
        2  => ['Lembut suara, Pendiam', 'Optimistik, Visioner', 'Pusat Perhatian, Suka gaul', 'Pendamai, Membawa harmoni'],
        3  => ['Menyemangati orang', 'Berusaha sempurna', 'Bagian dari kelompok', 'Ingin membuat tujuan'],
        4  => ['Menjadi frustrasi', 'Menyimpan perasaan saya', 'Menceritakan sisi saya', 'Siap beroposisi'],
        9  => ['Hasil adalah penting', 'Lakukan dengan benar, Akurasi penting', 'Dibuat menyenangkan', 'Mari kerjakan bersama'],
        10 => ['Akan berjalan terus tanpa kontrol diri', 'Akan membeli sesuai dorongan hati', 'Akan menunggu, Tanpa tekanan', 'Akan mengusahakan yang kuinginkan'],
        11 => ['Ramah, Mudah bergabung', 'Unik, Bosan rutinitas', 'Aktif mengubah sesuatu', 'Ingin hal-hal yang pasti'],
        12 => ['Non-konfrontasi, Menyerah', 'Dipenuhi hal detail', 'Perubahan pada menit terakhir', 'Menuntut, Kasar'],
        17 => ['Pendidikan, Kebudayaan', 'Prestasi, Ganjaran', 'Keselamatan, Keamanan', 'Sosial, Perkumpulan kelompok'],
        18 => ['Memimpin, Pendekatan langsung', 'Suka bergaul, Antusias', 'Dapat diramal, Konsisten', 'Waspada, Hati-hati'],
        19 => ['Tidak mudah dikalahkan', 'Kerjakan sesuai perintah, Ikut pimpinan', 'Mudah terangsang, Riang', 'Ingin segalanya teratur, Rapi'],
        20 => ['Saya akan pimpin mereka', 'Saya akan melaksanakan', 'Saya akan meyakinkan mereka', 'Saya dapatkan fakta'],
    ];

    public function run(): void
    {
        if (Quiz::where('name', 'DISC')->exists()) {
            $this->command?->warn('Kuis "DISC" sudah ada, dilewati.');
            return;
        }

        $quiz = Quiz::create([
            'name'         => 'DISC',
            'type'         => 'disc',
            'instructions' => "Setiap nomor memuat 4 (empat) kalimat.\n1. Pilih kalimat yang PALING menggambarkan diri Anda (MOST).\n2. Pilih kalimat yang PALING TIDAK menggambarkan diri Anda (LEAST).\nSetiap nomor HANYA 1 (satu) pilihan di MOST dan HANYA 1 (satu) di LEAST.",
        ]);

        $sort = 0;
        foreach (self::ITEMS as $no => $options) {
            $section = $quiz->sections()->create(['name' => "Nomor {$no}", 'sort_order' => $sort]);

            foreach (['MOST — kalimat yang PALING menggambarkan diri Anda', 'LEAST — kalimat yang PALING TIDAK menggambarkan diri Anda'] as $question) {
                $quiz->questions()->create([
                    'quiz_section_id' => $section->id,
                    'question'        => $question,
                    'options'         => $options,
                    'correct_option'  => '', // DISC tidak punya kunci
                    'sort_order'      => $sort++,
                ]);
            }
        }
    }
}
