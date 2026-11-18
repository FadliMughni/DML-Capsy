<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** Akun demo. Semua password: "password" (di-hash otomatis oleh cast model). */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Rina Marketing', 'email' => 'marketing@dml.test', 'role' => UserRole::Marketing],
            ['name' => 'Budi Gudang', 'email' => 'gudang@dml.test', 'role' => UserRole::Gudang],
            ['name' => 'Member Demo', 'email' => 'member@dml.test', 'role' => UserRole::Member],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrNew(['email' => $account['email']]);
            $user->fill($account + ['password' => 'password']);
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
