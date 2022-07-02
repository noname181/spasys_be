<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\User;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $adminRole = Role::create(['name' => 'Admin']);
        $adminPermission = Permission::create(['name' => 'all.control']);
        $adminRole->givePermissionTo($adminPermission);

        $customerRole = Role::create(['name' => 'Customer']);
        $customerPermission = Permission::create(['name' => 'user_account.control']);
        $customerRole->givePermissionTo($customerPermission);

        User::find(1)->assignRole('Admin');
        User::find(4)->assignRole('Customer');
    }
}
