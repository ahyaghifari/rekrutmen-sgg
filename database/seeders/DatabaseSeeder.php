<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // php artisan db:seed --class=RolesAndPermissionsSeeder --force
        // cd "d:\SGG\Projects\rekrutmen-sgg"; php artisan shield:generate --all --panel=admin 2>&1; php artisan shield:super-admin --user=1 2>&1

        User::updateOrCreate(
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
            RolesAndPermissionsSeeder::class,
            FakeApplicationsSeeder::class,
        ]);
    }
}
