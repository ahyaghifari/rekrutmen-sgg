<?php

namespace Database\Seeders;

use App\Models\JobOpening;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobOpeningSeeder extends Seeder
{
    /**
     * Seed lowongan dummy untuk SGG. Karena job_openings adalah cache lokal
     * read-only (bukan sumber kebenaran, lihat PRD §4c), data ini hanya untuk
     * memungkinkan form lamaran & FK berfungsi di tahap development.
     */
    public function run(): void
    {
        $openings = [
            [
                'title' => 'Perawat (Nurse)',
                'company_name' => 'PT Syifa Medical Center',
                'quota' => 5,
                'description' => 'Memberikan asuhan keperawatan profesional kepada pasien di fasilitas kesehatan Syifa Medical Center, termasuk pemantauan kondisi pasien, pemberian obat, dan koordinasi dengan dokter.',
                'requirements' => "- Minimal D3 Keperawatan\n- Memiliki STR yang masih berlaku\n- Pengalaman minimal 1 tahun (fresh graduate dipertimbangkan)\n- Bersedia bekerja shift",
            ],
            [
                'title' => 'Bidan (Midwife)',
                'company_name' => 'PT Syifa Medical Center',
                'quota' => 3,
                'description' => 'Menangani pelayanan kebidanan, pemeriksaan kehamilan, persalinan, dan kesehatan reproduksi perempuan.',
                'requirements' => "- Minimal D3 Kebidanan\n- Memiliki STRB yang masih berlaku\n- Memiliki pengalaman praktik\n- Berorientasi pelayanan dan empati tinggi",
            ],
            [
                'title' => 'Admin Klinik',
                'company_name' => 'PT Syifa Medical Center',
                'quota' => 2,
                'description' => 'Mengelola administrasi klinik sehari-hari: pendaftaran pasien, pendataan rekam medis, penagihan, dan koordinasi jadwal dokter.',
                'requirements' => "- Minimal SMA/SMK\n- Menggunakan komputer (Office) dengan lancar\n- Komunikatif dan teliti\n- Pengalaman admin/CS menjadi nilai plus",
            ],
            [
                'title' => 'Apoteker',
                'company_name' => 'PT Global Syifa Farmasi',
                'quota' => 2,
                'description' => 'Mengelola dispensing obat resep, konseling pasien, pengelolaan stok obat, dan kepatuhan regulasi farmasi.',
                'requirements' => "- Sarjana Farmasi (S1)\n- Memiliki STRA yang masih berlaku\n- Paham regulasi perbekalan farmasi\n- Teliti dan berorientasi detail",
            ],
            [
                'title' => 'Staff HR & Recruitment',
                'company_name' => 'PT Syifa Global Utama',
                'quota' => 1,
                'description' => 'Mendukung proses rekrutmen, onboarding karyawan, pengelolaan data karyawan, dan administrasi kepegawaian.',
                'requirements' => "- Minimal S1 Psikologi/Manajemen/HR\n- Pengalaman rekrutmen minimal 1 tahun\n- Komunikasi dan negosiasi baik\n- Mengenal employment regulation dasar",
            ],
            [
                'title' => 'Marketing Executive',
                'company_name' => 'PT Syifa Karya Sejahtera',
                'quota' => 4,
                'description' => 'Mengembangkan jaringan klien, menjual layanan/produk perusahaan, dan mencapai target sales bulanan.',
                'requirements' => "- Minimal SMA/SMK atau D3\n- Pengalaman sales/marketing menjadi nilai plus\n- Komunikatif dan mandiri\n- Bersedia mobilisasi area",
            ],
        ];

        foreach ($openings as $data) {
            JobOpening::create(array_merge($data, [
                'id' => (string) Str::ulid(),
                'is_active' => true,
            ]));
        }
    }
}
