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
        $admin = User::where('email', 'admin@admin.com')->first();

        if ($admin) {
            $admin->update([
                'first_name' => 'EchoMail',
                'last_name' => 'Admin',
                'phone' => '+234 900 000 0000',
                'password' => Hash::make('Admin@12345'),
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]);
            return;
        }

        User::create([
            'uuid' => Str::uuid(),
            'first_name' => 'EchoMail',
            'last_name' => 'Admin',
            'email' => 'admin@admin.com',
            'phone' => '+234 900 000 0000',
            'password' => Hash::make('Admin@12345'),
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);
    }
}
