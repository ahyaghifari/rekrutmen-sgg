<?php

namespace Database\Seeders;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeApplicationsHrisSeeder extends Seeder
{
    private const JOB_OPENING_ID = '01KZKGVRB38FZQVMV765X09AR2';
    private const COUNT = 231;

    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $genders      = ['Laki-laki', 'Perempuan'];
        $educations   = ['D3', 'S1', 'S2'];
        $majors       = ['Teknik Informatika', 'Sistem Informasi', 'Ilmu Komputer', 'Teknik Elektro', 'Matematika', 'Fisika'];
        $schools      = ['Universitas Indonesia', 'Institut Teknologi Bandung', 'Universitas Gadjah Mada', 'Universitas Brawijaya', 'Universitas Mulawarman', 'Universitas Hasanuddin', 'ITS Surabaya'];
        $relocate     = ['Ya', 'Tidak'];
        $availability = ['Segera (< 1 bulan)', '1-3 bulan', '> 3 bulan'];

        for ($i = 1; $i <= self::COUNT; $i++) {
            $gender    = $faker->randomElement($genders);
            $firstName = $gender === 'Laki-laki' ? $faker->firstNameMale() : $faker->firstNameFemale();
            $fullName  = $firstName . ' ' . $faker->lastName();
            $email     = 'fake.itrd.' . $i . '.' . Str::random(4) . '@example.com';

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

            $year = $faker->numberBetween(2010, 2024);

            $answers = [
                'full_name'             => $fullName,
                'gender'                => $gender,
                'whatsapp_number'       => '08' . $faker->numerify('#########'),
                'domicile_address'      => $faker->address(),
                'birth_place'           => $faker->city(),
                'birth_date'            => $faker->date('Y-m-d', '-22 years'),
                'instagram_handle'      => '@' . Str::slug($firstName) . $faker->numberBetween(10, 999),
                'linkedin_url'          => 'https://linkedin.com/in/' . Str::slug($fullName),
                'education_level'       => $faker->randomElement($educations),
                'major'                 => $faker->randomElement($majors),
                'school_name'           => $faker->randomElement($schools),
                'graduation_year'       => (string) $year,
                'other_position_note'   => 'IT R&D Staff',
                'work_experience_note'  => $faker->sentence(10),
                'willing_relocate'      => $faker->randomElement($relocate),
                'last_salary'           => (string) ($faker->numberBetween(4, 10) * 500000),
                'expected_salary'       => (string) ($faker->numberBetween(6, 15) * 500000),
                'availability'          => $faker->randomElement($availability),
            ];

            foreach ($answers as $key => $value) {
                $application->answers()->create([
                    'field_key' => $key,
                    'value'     => $value,
                ]);
            }
        }

        $this->command->info('231 fake applications seeded for IT R&D Staff.');
    }
}
