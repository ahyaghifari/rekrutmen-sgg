<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['laki_laki', 'perempuan']);

        return [
            'user_id' => User::factory(),
            'job_opening_id' => JobOpening::factory(),
            'full_name' => fake()->name($gender === 'laki_laki' ? 'male' : 'female'),
            'gender' => $gender,
            'whatsapp_number' => '08' . fake()->numberBetween(1000000000, 99999999999),
            'age' => fake()->numberBetween(20, 45),
            'domicile_address' => fake()->address(),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-20 years')->format('Y-m-d'),
            'instagram_handle' => '@' . fake()->userName(),
            'linkedin_url' => fake()->optional()->url(),
            'education_level' => fake()->randomElement(['sma_smk', 'd3', 'd4', 's1', 's2', 'profesi']),
            'major' => fake()->words(2, true),
            'school_name' => fake()->company() . ' University',
            'graduation_year' => fake()->numberBetween(2015, (int) date('Y')),
            'str_valid_until' => null,
            'other_position_note' => fake()->optional()->sentence(),
            'work_experience_note' => fake()->optional()->paragraph(),
            'willing_relocate' => fake()->boolean(),
            'last_salary' => fake()->numberBetween(3000000, 10000000),
            'expected_salary' => fake()->numberBetween(5000000, 15000000),
            'availability' => fake()->randomElement(['immediately', '2_weeks', '1_month', 'more_than_2_months']),
            'certificate_file_path' => null,
            'cv_file_path' => 'cvs/' . fake()->uuid() . '.pdf',
            'status' => 'applied',
        ];
    }
}
