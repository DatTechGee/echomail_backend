<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@admin.com')
            ->orWhere('email', 'admin@cashapp.com')
            ->first();

        if ($admin) {
            $admin->update([
                'email' => 'admin@admin.com',
                'phone' => '+1234567890',
                'password' => Hash::make('Admin@12345'),
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]);
            return;
        }

        User::create([
            'uuid' => Str::uuid(),
            'first_name' => 'LevelUp',
            'last_name' => 'Admin',
            'email' => 'admin@admin.com',
            'phone' => '+1234567890',
            'password' => Hash::make('Admin@12345'),
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);
    }
}
