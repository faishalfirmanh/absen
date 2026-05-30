<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserLoginSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'username' => 'admin',
                'fullname' => 'Administrator',
                'username_machine' => 'device_admin',
                'password_machine' => Hash::make('admin123'),
                'is_login_device' => false,
            ],
            [
                'username' => 'hrd',
                'fullname' => 'HRD Manager',
                'username_machine' => 'device_hrd',
                'password_machine' => Hash::make('hrd123'),
                'is_login_device' => false,
            ],
            [
                'username' => 'isal',
                'fullname' => 'faishal firman hakim',
                'username_machine' => 'isal123',
                'password_machine' => Hash::make('isal123'),
                'is_login_device' => false,
                'location' => 1,
            ],
            [
                'username' => 'accounting_haidar',
                'username_machine' => 'haidar123',
                'password_machine' => Hash::make('haidar123'),
                'is_login_device' => false,
                'location' => 1,
            ],
            [
                'username' => 'digmarfahrul',
                'username_machine' => 'fahrul123',
                'password_machine' => Hash::make('fahrul123'),
                'is_login_device' => false,
                'location' => 1,
            ],
            [
                'username' => 'hrdmila',
                'username_machine' => 'mila123',
                'password_machine' => Hash::make('mila123'),
                'is_login_device' => false,
                'location' => 1,
            ],
        ];

        foreach ($users as $userData) {
            // 1. Cari user berdasarkan kolom 'username'
            $user = User::where('username', $userData['username'])->first();

            // 2. Jika user ditemukan di database DAN 'username_machine' di array tidak kosong/null
            if ($user && !empty($userData['username_machine'])) {

                // Siapkan data yang akan di-update
                $dataToUpdate = [
                    'username_machine' => $userData['username_machine'],
                    'password_machine' => $userData['password_machine'],
                    'is_login_device' => $userData['is_login_device'],
                ];

                // 3. Karena tidak semua user di array punya key 'location', kita cek dulu
                if (isset($userData['location'])) {
                    $dataToUpdate['location'] = $userData['location'];
                }

                // 4. Lakukan update data
                $user->update($dataToUpdate);
            }
        }

        $this->command->info('✅ Users table seeded successfully!');
        $this->command->info('   Login with username_machine + password_machine:');
        $this->command->info('   • device_admin / admin123');
        $this->command->info('   • device_hrd / hrd123');
        $this->command->info('   • isal123 / isal123'); // Pesan info diperbaiki (sebelumnya device_acc)
    }
}