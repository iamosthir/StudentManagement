<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AddProductPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Add missing permissions
        $newPermissions = [
            'view subscription options',
            'create subscription options',
            'edit subscription options',
            'delete subscription options',
            'view products',
            'create products',
            'edit products',
            'delete products',
        ];

        foreach ($newPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }

        // Give all permissions to Administrator role
        $adminRole = Role::where('name', 'Administrator')
            ->where('guard_name', 'admin')
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(Permission::all());
        }

        // Give view permissions to Registrar role
        $registrarRole = Role::where('name', 'Registrar')
            ->where('guard_name', 'admin')
            ->first();

        if ($registrarRole) {
            $registrarRole->givePermissionTo([
                'view subscription options',
                'view products',
            ]);
        }

        $this->command->info('Product and Subscription Option permissions added successfully!');
    }
}
