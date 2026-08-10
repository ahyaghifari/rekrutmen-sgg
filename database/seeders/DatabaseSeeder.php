<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'ahyaghifari288@gmail.com'],
            [
                'name'              => 'MUHAMMAD AHYA GHIFARI',
                'password'          => Hash::make('password'),
                'is_staff'          => true,
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            CompanySeeder::class,
            JobOpeningSeeder::class,
            FormSectionsSeeder::class,
        ]);
    }
}
