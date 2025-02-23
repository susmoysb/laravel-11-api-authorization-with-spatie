<?php

namespace Database\Seeders;

use App\Classes\BaseClass;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create roles
        $superAdminRole = Role::create(['name' => BaseClass::ROLES['superAdmin']]);
        $adminRole      = Role::create(['name' => BaseClass::ROLES['admin']]);
        $userRole       = Role::create(['name' => BaseClass::ROLES['user']]);

        // create permission groups and permissions
        foreach (BaseClass::PERMISSIONS as $groupName => $permissions) {
            $permissionGroupId = DB::table('permission_groups')->insertGetId(['name' => ucwords(str_replace("_", " ", $groupName))]);
            foreach ($permissions as $permission) {
                Permission::create([
                    'name' => $permission,
                    'permission_group_id' => $permissionGroupId
                ]);
            }
        }

        // assign permissions to roles
        $superAdminRole->syncPermissions(BaseClass::PERMISSIONS); // assign all permissions to super admin role
        $adminRole->syncPermissions(BaseClass::PERMISSIONS['user']); // assign user permissions to admin role
        $userRole->syncPermissions(BaseClass::PERMISSIONS['own_profile']); // assign own profile permissions to user role

        // assign roles to users
        User::find(1)->assignRole($superAdminRole); // assign super admin role to super admin user
        User::find(2)->assignRole($adminRole); // assign admin role to admin user

        User::whereNotIn('id', [1])->each(fn($user) => $user->assignRole($userRole)); // assign user role to all other users

    }
}
