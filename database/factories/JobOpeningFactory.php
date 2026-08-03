<?php

namespace Database\Factories;

use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companies = [
            'PT Syifa Global Utama',
            'PT Syifa Medical Center',
            'PT Syifa Karya Sejahtera',
            'PT Global Syifa Farmasi',
            'Yayasan Syifa Global',
        ];

        return [
            'id' => (string) Str::ulid(),
            'title' => fake()->jobTitle(),
            'company_name' => fake()->randomElement($companies),
            'quota' => fake()->numberBetween(1, 5),
            'is_active' => true,
            'description' => fake()->paragraphs(2, true),
            'requirements' => "- " . collect(range(1, 4))->map(fn () => fake()->sentence())->implode("\n- "),
        ];
    }
}
