<?php

namespace Database\Seeders;

use App\Models\FormField;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeApplicationsSeeder extends Seeder
{
    /**
     * Konfigurasi: [company name => [job title => count]]
     * Sesuaikan sebelum jalankan atau sesuai JobOpeningSeeder.
     */
    private array $config = [
        'RSU Syifa Medika Banjarbaru' => [
            'Perawat (Nurse)'  => 15,
            'Bidan (Midwife)'  => 10,
        ],
        'RSU Syifa Medika Barabai' => [
            'Admin Klinik'     => 8,
        ],
        'Syifa Global Med' => [
            'Apoteker'         => 8,
        ],
        'Syifa Global Group' => [
            'Staff HR & Recruitment' => 10,
        ],
        'Lerina Inn' => [
            'Marketing Executive'    => 12,
        ],
        'Syifa Agro Natura' => [
            'Staff Lapangan Pertanian' => 8,
        ],
        'Syifa Global Lestari Banjarbaru' => [
            'IT Support'       => 8,
        ],
    ];

    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        foreach ($this->config as $companyName => $openings) {
            foreach ($openings as $jobTitle => $count) {
                $jobOpening = JobOpening::where('title', $jobTitle)
                    ->where(fn ($q) => $q
                        ->whereHas('company', fn ($q2) => $q2->where('name', $companyName))
                        ->orWhere('company_name', $companyName)
                    )->first();

                if (! $jobOpening) {
                    $this->command->warn("Lowongan tidak ditemukan: [{$companyName}] {$jobTitle}");
                    continue;
                }

                // Ambil semua field form perusahaan ini (exclude file)
                $fields = FormField::whereHas('section', fn ($q) =>
                    $q->whereHas('company', fn ($q2) => $q2->where('name', $companyName))
                )->where('type', '!=', 'file')->orderBy('sort_order')->get();

                // Fallback: perusahaan tanpa form pakai field dasar SGG Holding
                if ($fields->isEmpty()) {
                    $fields = FormField::whereHas('section', fn ($q) =>
                        $q->whereHas('company', fn ($q2) => $q2->where('name', 'Syifa Global Group'))
                    )->where('type', '!=', 'file')->orderBy('sort_order')->get();
                }

                $this->command->info("Membuat {$count} pelamar untuk [{$companyName}] {$jobTitle}...");

                for ($i = 1; $i <= $count; $i++) {
                    $gender    = $faker->randomElement(['Laki-laki', 'Perempuan']);
                    $firstName = $gender === 'Laki-laki' ? $faker->firstNameMale() : $faker->firstNameFemale();
                    $fullName  = $firstName . ' ' . $faker->lastName();
                    $email     = 'fake.' . Str::slug($companyName, '') . '.' . $i . '.' . Str::random(4) . '@example.com';
                    $birthYear = $faker->numberBetween(1985, 2003);

                    $user = User::create([
                        'name'              => $fullName,
                        'email'             => $email,
                        'password'          => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]);

                    $application = JobApplication::create([
                        'user_id'        => $user->id,
                        'job_opening_id' => $jobOpening->id,
                        'status'         => 'applied',
                    ]);

                    foreach ($fields as $field) {
                        $value = $this->generateValue($faker, $field, $fullName, $gender, $birthYear);
                        if ($value === null) continue;

                        $application->answers()->create([
                            'field_key' => $field->name,
                            'value'     => $value,
                        ]);
                    }

                    $this->command->getOutput()->write('.');
                }

                $this->command->info("\n{$count} pelamar berhasil dibuat untuk {$jobTitle}.");
            }
        }
    }

    private function generateValue(\Faker\Generator $faker, FormField $field, string $fullName, string $gender, int $birthYear): ?string
    {
        $cities = ['Banjarmasin', 'Banjarbaru', 'Barabai', 'Kandangan', 'Martapura', 'Amuntai', 'Rantau', 'Tanjung'];

        return match ($field->name) {
            'full_name'            => $fullName,
            'nik'                  => (string) $faker->numerify('################'),
            'gender'               => $gender,
            'birth_place'          => $faker->randomElement($cities),
            'birth_date'           => $birthYear . '-' . str_pad($faker->numberBetween(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad($faker->numberBetween(1, 28), 2, '0', STR_PAD_LEFT),
            'domicile_address'     => $faker->streetAddress() . ', ' . $faker->randomElement($cities),
            'whatsapp_number'      => '08' . $faker->numerify('#########'),
            'instagram_handle'     => '@' . Str::slug(explode(' ', $fullName)[0]) . $faker->numberBetween(10, 999),
            'linkedin_url'         => 'https://linkedin.com/in/' . Str::slug($fullName),
            'education_level'      => $this->randomChoice($faker, $field, ['D-III', 'S-I']),
            'major'                => $faker->randomElement(['Kebidanan', 'Keperawatan', 'Kesehatan Masyarakat', 'Farmasi']),
            'school_name'          => $faker->randomElement(['Poltekkes Banjarmasin', 'Universitas Lambung Mangkurat', 'Universitas Mulawarman', 'STIKES Borneo Lestari', 'STIKES Suaka Insan']),
            'graduation_year'      => (string) $faker->numberBetween($birthYear + 18, min($birthYear + 26, (int) date('Y'))),
            'str_valid_until'      => now()->addYears($faker->numberBetween(1, 5))->format('Y-m-d'),
            'tinggi_badan'         => (string) $faker->numberBetween(150, 175),
            'berat_badan'          => (string) $faker->numberBetween(45, 75),
            'riwayat_sakit'        => $faker->randomElement(['Tidak ada riwayat penyakit serius', 'Alergi debu ringan', 'Tidak ada']),
            'pelatihan'            => $faker->randomElement(['BTCLS 2023', 'APN 2022', 'Pelatihan Asuhan Kebidanan 2023', 'Tidak ada']),
            'work_experience_note' => $faker->paragraph(2),
            'other_position_note'  => $faker->randomElement(['Staff Administrasi', 'Tenaga Kesehatan Lain', '-']),
            'willing_relocate'     => $this->randomChoice($faker, $field, ['Ya', 'Tidak']),
            'last_salary'          => (string) ($faker->numberBetween(3, 8) * 500000),
            'expected_salary'      => (string) ($faker->numberBetween(4, 12) * 500000),
            'availability'         => $this->randomChoice($faker, $field, ['Segera', '2 Minggu', '1 Bulan', '>2 Bulan']),
            'motivasi'             => 'Saya ingin berkontribusi di ' . $faker->randomElement(['RSU Syifa Medika', 'Syifa Global Group']) . ' karena memiliki visi yang sejalan dengan passion saya di bidang kesehatan.',
            'hubungan_keluarga'    => $faker->randomElement(['Tidak ada', 'Tidak ada hubungan keluarga dengan karyawan']),
            default                => $this->generateFromType($faker, $field),
        };
    }

    private function randomChoice(\Faker\Generator $faker, FormField $field, array $fallback): string
    {
        $choices = $field->options['choices'] ?? $fallback;
        return $faker->randomElement($choices);
    }

    private function generateFromType(\Faker\Generator $faker, FormField $field): ?string
    {
        return match ($field->type) {
            'text'     => $faker->words(3, true),
            'textarea' => $faker->paragraph(),
            'number'   => (string) $faker->numberBetween(1, 100),
            'date'     => $faker->date('Y-m-d'),
            'radio'    => isset($field->options['choices']) ? $faker->randomElement($field->options['choices']) : 'Ya',
            'file'     => null,
            default    => null,
        };
    }
}
