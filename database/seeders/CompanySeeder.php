<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['name' => 'Syifa Global Group',              'hris_code' => '0000'],
            ['name' => 'RSU Syifa Medika Banjarbaru',     'hris_code' => '0001'],
            ['name' => 'RSU Syifa Medika Barabai',        'hris_code' => '0002'],
            ['name' => 'Syifa Global Med',                'hris_code' => '0003'],
            ['name' => 'Syifa Global Lestari Banjarbaru', 'hris_code' => '0004'],
            // ['name' => 'Syifa Global Lestari Surabaya',   'hris_code' => '0005'],
            ['name' => 'Lerina Inn',                      'hris_code' => '0006'],
            // ['name' => 'Syifa Agro Natura',               'hris_code' => '0007'],
        ];

        foreach ($companies as $data) {
            Company::create([
                'hris_code'   => $data['hris_code'],
                'name'        => $data['name'],
                'brand_color' => '#042c6c',
                'logo_path'   => null,
                'email'       => null,
                'phone'       => null,
            ]);
        }
    }
}
 