<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserLoginSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data (optional - remove if you don't want to delete)
        // User::truncate();

        $users = [
            [
                'username' => 'admin',
                //'password' => Hash::make('password123'),     // regular password (backup)
                'fullname' => 'Administrator',
                'username_machine' => 'device_admin',
                'password_machine' => Hash::make('admin123'),         // ← used for Android login
                'is_login_device' => false,
            ],

            [
                'username' => 'hrd',
               // 'password' => Hash::make('password123'),
                'fullname' => 'HRD Manager',
                'username_machine' => 'device_hrd',
                'password_machine' => Hash::make('hrd123'),
                'is_login_device' => false,
            ],

            [
                'username' => 'isal',
               // 'password' => Hash::make('isal123'),
                'fullname' => 'faishal firmnan hakim',
                'username_machine' => 'isal123',
                'password_machine' => Hash::make('isal123'),
                'is_login_device' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $cek = User::where('username_machine', 'isal123')->first();
        $cek->location = 1;
        $cek->save();

        $this->command->info('✅ Users table seeded successfully!');
        $this->command->info('   Login with username_machine + password_machine:');
        $this->command->info('   • device_admin / admin123');
        $this->command->info('   • device_hrd / hrd123');
        $this->command->info('   • device_acc / acc123');
    }
}