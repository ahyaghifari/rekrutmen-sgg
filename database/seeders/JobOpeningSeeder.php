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
                'company' => 'Syifa Global Group',
                'title'   => 'Staff HR & Recruitment',
                'quota'   => 1,
                'description'  => 'Mendukung proses rekrutmen, onboarding karyawan, pengelolaan data karyawan, dan administrasi kepegawaian.',
                'requirements' => "- Minimal S1 Psikologi/Manajemen/HR\n- Pengalaman rekrutmen minimal 1 tahun\n- Komunikasi dan negosiasi baik\n- Mengenal employment regulation dasar",
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
