<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobOpening;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobOpeningSeeder extends Seeder
{
    public function run(): void
    {
        $co = Company::pluck('id', 'name');

        $openings = [
            [
                'company' => 'RSU Syifa Medika Banjarbaru',
                'title'   => 'Perawat (Nurse)',
                'quota'   => 5,
                'description'  => 'Memberikan asuhan keperawatan profesional kepada pasien di RSU Syifa Medika Banjarbaru, termasuk pemantauan kondisi pasien, pemberian obat, dan koordinasi dengan dokter.',
                'requirements' => "- Minimal D3 Keperawatan\n- Memiliki STR yang masih berlaku\n- Pengalaman minimal 1 tahun (fresh graduate dipertimbangkan)\n- Bersedia bekerja shift",
            ],
            [
                'company' => 'RSU Syifa Medika Banjarbaru',
                'title'   => 'Bidan (Midwife)',
                'quota'   => 3,
                'description'  => 'Menangani pelayanan kebidanan, pemeriksaan kehamilan, persalinan, dan kesehatan reproduksi perempuan.',
                'requirements' => "- Minimal D3 Kebidanan\n- Memiliki STRB yang masih berlaku\n- Memiliki pengalaman praktik\n- Berorientasi pelayanan dan empati tinggi",
            ],
            [
                'company' => 'RSU Syifa Medika Barabai',
                'title'   => 'Admin Klinik',
                'quota'   => 2,
                'description'  => 'Mengelola administrasi klinik sehari-hari: pendaftaran pasien, pendataan rekam medis, penagihan, dan koordinasi jadwal dokter.',
                'requirements' => "- Minimal SMA/SMK\n- Menggunakan komputer (Office) dengan lancar\n- Komunikatif dan teliti\n- Pengalaman admin/CS menjadi nilai plus",
            ],
            [
                'company' => 'Syifa Global Med',
                'title'   => 'Apoteker',
                'quota'   => 2,
                'description'  => 'Mengelola dispensing obat resep, konseling pasien, pengelolaan stok obat, dan kepatuhan regulasi farmasi.',
                'requirements' => "- Sarjana Farmasi (S1)\n- Memiliki STRA yang masih berlaku\n- Paham regulasi perbekalan farmasi\n- Teliti dan berorientasi detail",
            ],
            [
                'company' => 'Syifa Global Group',
                'title'   => 'Staff HR & Recruitment',
                'quota'   => 1,
                'description'  => 'Mendukung proses rekrutmen, onboarding karyawan, pengelolaan data karyawan, dan administrasi kepegawaian.',
                'requirements' => "- Minimal S1 Psikologi/Manajemen/HR\n- Pengalaman rekrutmen minimal 1 tahun\n- Komunikasi dan negosiasi baik\n- Mengenal employment regulation dasar",
            ],
            [
                'company' => 'Lerina Inn',
                'title'   => 'Marketing Executive',
                'quota'   => 4,
                'description'  => 'Mengembangkan jaringan klien, menjual layanan/produk Lerina Inn, dan mencapai target sales bulanan.',
                'requirements' => "- Minimal SMA/SMK atau D3\n- Pengalaman sales/marketing menjadi nilai plus\n- Komunikatif dan mandiri\n- Bersedia mobilisasi area",
            ],
            [
                'company' => 'Syifa Agro Natura',
                'title'   => 'Staff Lapangan Pertanian',
                'quota'   => 3,
                'description'  => 'Mendukung operasional lapangan pertanian dan agribisnis Syifa Agro Natura.',
                'requirements' => "- Minimal SMA/SMK Pertanian atau D3 Agribisnis\n- Bersedia bekerja di lapangan\n- Memiliki pengetahuan dasar pertanian\n- Sehat jasmani dan rohani",
            ],
            [
                'company' => 'Syifa Global Lestari Banjarbaru',
                'title'   => 'IT Support',
                'quota'   => 1,
                'description'  => 'Menangani dukungan teknis infrastruktur IT, jaringan, dan perangkat keras di lingkungan perusahaan.',
                'requirements' => "- Minimal D3 Teknik Informatika/Sistem Informasi\n- Menguasai troubleshooting hardware & software\n- Pengalaman IT support menjadi nilai plus\n- Responsif dan problem-solving",
            ],
        ];

        foreach ($openings as $data) {
            $companyId = $co[$data['company']] ?? null;
            JobOpening::create([
                'id'           => (string) Str::ulid(),
                'company_id'   => $companyId,
                'company_name' => $data['company'],
                'title'        => $data['title'],
                'quota'        => $data['quota'],
                'is_active'    => true,
                'description'  => $data['description'],
                'requirements' => $data['requirements'],
            ]);
        }
    }
}
