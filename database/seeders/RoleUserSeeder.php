<?php

namespace Database\Seeders;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        $creator = app(CreateNewUser::class);

        $accounts = [
            [
                'name' => 'Superadmin Madrasah',
                'email' => 'superadmin@madrasah.test',
                'role' => UserRole::Superadmin,
            ],
            [
                'name' => 'Guru Wali Kelas',
                'email' => 'walikelas@madrasah.test',
                'role' => UserRole::WaliKelas,
            ],
            [
                'name' => 'Guru Mata Pelajaran',
                'email' => 'gurumapel@madrasah.test',
                'role' => UserRole::GuruMapel,
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::where('email', $account['email'])->first();

            if (! $user) {
                $user = $creator->create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                ]);
            }

            $user->forceFill([
                'role' => $account['role'],
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
