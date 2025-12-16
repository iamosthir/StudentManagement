<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Administrator
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('Administrator');

        // Create Accountant
        $accountant = Admin::create([
            'name' => 'Accountant User',
            'email' => 'accountant@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $accountant->assignRole('Accountant');

        // Create Registrar
        $registrar = Admin::create([
            'name' => 'Registrar User',
            'email' => 'registrar@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $registrar->assignRole('Registrar');

        $this->command->info('Admin users created successfully!');
        $this->command->info('');
        $this->command->info('Administrator:');
        $this->command->info('  Email: admin@example.com');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Accountant:');
        $this->command->info('  Email: accountant@example.com');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Registrar:');
        $this->command->info('  Email: registrar@example.com');
        $this->command->info('  Password: password');
    }
}
