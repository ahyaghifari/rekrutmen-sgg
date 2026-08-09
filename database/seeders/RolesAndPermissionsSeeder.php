<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $user = User::where('email', 'ahyaghifari288@gmail.com')->firstOrFail();
        $user->assignRole($superAdmin);
        $user->is_staff = true;
        $user->save();

        $this->command->info('Roles created. ahyaghifari288@gmail.com assigned Super Admin.');
    }
}
