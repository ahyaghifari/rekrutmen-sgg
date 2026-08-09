<?php

namespace Database\Seeders;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeApplicationsSeeder extends Seeder
{
    private const JOB_OPENING_ID = '01KZGQZ7JAWYN1PHVKK4XYRFHF';
    private const COUNT = 100;

    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $genders      = ['Laki-laki', 'Perempuan'];
        $educations   = ['SMA/SMK', 'D3', 'S1', 'S2'];
        $majors       = ['Manajemen SDM', 'Psikologi', 'Administrasi Bisnis', 'Manajemen', 'Hukum', 'Akuntansi'];
        $schools      = ['Universitas Indonesia', 'Universitas Gadjah Mada', 'Universitas Brawijaya', 'Universitas Lambung Mangkurat', 'Universitas Mulawarman', 'STIE Indonesia'];
        $relocate     = ['Ya', 'Tidak'];
        $availability = ['Segera (< 1 bulan)', '1-3 bulan', '> 3 bulan'];

        for ($i = 1; $i <= self::COUNT; $i++) {
            $gender    = $faker->randomElement($genders);
            $firstName = $gender === 'Laki-laki' ? $faker->firstNameMale() : $faker->firstNameFemale();
            $fullName  = $firstName . ' ' . $faker->lastName();
            $email     = 'fake.applicant.' . $i . '.' . Str::random(4) . '@example.com';

            $user = User::create([
                'name'              => $fullName,
                'email'             => $email,
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            $application = JobApplication::create([
                'user_id'        => $user->id,
                'job_opening_id' => self::JOB_OPENING_ID,
                'status'         => 'applied',
            ]);

            $year = $faker->numberBetween(2005, 2023);

            $answers = [
                'full_name'             => $fullName,
                'gender'                => $gender,
                'whatsapp_number'       => '08' . $faker->numerify('#########'),
                'domicile_address'      => $faker->address(),
                'birth_place'           => $faker->city(),
                'birth_date'            => $faker->date('Y-m-d', '-20 years'),
                'instagram_handle'      => '@' . Str::slug($firstName) . $faker->numberBetween(10, 999),
                'linkedin_url'          => 'https://linkedin.com/in/' . Str::slug($fullName),
                'education_level'       => $faker->randomElement($educations),
                'major'                 => $faker->randomElement($majors),
                'school_name'           => $faker->randomElement($schools),
                'graduation_year'       => (string) $year,
                'other_position_note'   => 'Staff HR & Recruitment',
                'work_experience_note'  => $faker->sentence(10),
                'willing_relocate'      => $faker->randomElement($relocate),
                'last_salary'           => (string) ($faker->numberBetween(3, 8) * 500000),
                'expected_salary'       => (string) ($faker->numberBetween(4, 12) * 500000),
                'availability'          => $faker->randomElement($availability),
            ];

            foreach ($answers as $key => $value) {
                $application->answers()->create([
                    'field_key' => $key,
                    'value'     => $value,
                ]);
            }
        }

        $this->command->info('100 fake applications seeded for Staff HR & Recruitment.');
    }
}
