<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Super Admin role
        $superAdminRole = Role::where('slug', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run migrations first.');
            return;
        }

        // Check if super admin already exists
        $existingSuperAdmin = User::where('role_id', $superAdminRole->id)->first();

        if ($existingSuperAdmin) {
            $this->command->info('Super Admin already exists: ' . $existingSuperAdmin->email);
            return;
        }

        // Create Super Admin user
        $superAdmin = User::create([
            'id' => Str::uuid(),
            'name' => 'Kamrul Hasan',
            'username' => 'kamrul',
            'email' => 'hasanmk690@gmail.com',
            'email_verified_at' => now(),
            'phone' => '01814340440',
            'phone_verified_at' => now(),
            'password' => Hash::make('jamai420'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: ' . $superAdmin->email);
        $this->command->info('Password: jamai420');
        $this->command->warn('IMPORTANT: Change the password after first login!');
    }
}
