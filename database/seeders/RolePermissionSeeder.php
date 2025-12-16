<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for admin guard
        $permissions = [
            // Student management
            'view students',
            'create students',
            'edit students',
            'delete students',
            'archive students',

            // Program management
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',

            // Subscription Option management
            'view subscription options',
            'create subscription options',
            'edit subscription options',
            'delete subscription options',

            // Product management
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Subscription management
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',

            // Payment management
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',

            // Wallet management
            'view wallets',
            'manage wallets',
            'create transfers',

            // Expense management
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',

            // User management
            'view admins',
            'create admins',
            'edit admins',
            'delete admins',

            // Role & Permission management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'assign roles',

            // Reports
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }

        // Create roles and assign permissions

        // Administrator - full access
        $adminRole = Role::create([
            'name' => 'Administrator',
            'guard_name' => 'admin',
        ]);
        $adminRole->givePermissionTo(Permission::all());

        // Accountant - financial operations only
        $accountantRole = Role::create([
            'name' => 'Accountant',
            'guard_name' => 'admin',
        ]);
        $accountantRole->givePermissionTo([
            'view students',
            'view subscriptions',
            'view payments',
            'create payments',
            'edit payments',
            'view wallets',
            'manage wallets',
            'create transfers',
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'view reports',
            'export reports',
        ]);

        // Registrar - student and subscription management
        $registrarRole = Role::create([
            'name' => 'Registrar',
            'guard_name' => 'admin',
        ]);
        $registrarRole->givePermissionTo([
            'view students',
            'create students',
            'edit students',
            'archive students',
            'view programs',
            'view subscription options',
            'view products',
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'view payments',
            'view reports',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('- Administrator (full access)');
        $this->command->info('- Accountant (financial operations)');
        $this->command->info('- Registrar (student & subscription management)');
    }
}
