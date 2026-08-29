<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $perawat   = Role::firstOrCreate(['name' => 'Perawat', 'guard_name' => 'web']);
        $lab       = Role::firstOrCreate(['name' => 'Staff Lab', 'guard_name' => 'web']);
        $radiologi = Role::firstOrCreate(['name' => 'Staff Radiologi', 'guard_name' => 'web']);
        $dokter    = Role::firstOrCreate(['name' => 'Dokter', 'guard_name' => 'web']);

        $fillNurse     = Permission::firstOrCreate(['name' => 'fill_mcu_nurse', 'guard_name' => 'web']);
        $fillLab       = Permission::firstOrCreate(['name' => 'fill_mcu_lab', 'guard_name' => 'web']);
        $fillRadiology = Permission::firstOrCreate(['name' => 'fill_mcu_radiology', 'guard_name' => 'web']);
        $fillDoctor    = Permission::firstOrCreate(['name' => 'fill_mcu_doctor', 'guard_name' => 'web']);

        $perawat->givePermissionTo($fillNurse);
        $lab->givePermissionTo($fillLab);
        $radiologi->givePermissionTo($fillRadiology);
        $dokter->givePermissionTo($fillDoctor);

        // Super Admin selalu punya semua permission yang ada — termasuk yang baru dibuat Shield
        // untuk resource/page baru, supaya nav/halaman baru tidak pernah kegate diam-diam.
        $superAdmin->syncPermissions(Permission::all());

        $user = User::where('email', 'ahyaghifari288@gmail.com')->firstOrFail();
        $user->assignRole($superAdmin);
        $user->is_staff = true;
        $user->save();

        $this->command->info('Roles created. ahyaghifari288@gmail.com assigned Super Admin.');
    }
}
