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
        User::create([
            'uuid' => Str::uuid(),
            'first_name' => 'LevelUp',
            'last_name' => 'Admin',
            'email' => 'admin@levelup.com',
            'phone' => '+1234567890',
            'password' => Hash::make('password123'), // Change this in production
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);
    }
}
