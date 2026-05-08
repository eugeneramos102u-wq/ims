<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seeds the three default user accounts (Admin, Manager, Cashier).
     * Idempotent — safe to re-run; existing accounts are not overwritten.
     *
     * Run standalone:  php artisan db:seed --class=UserSeeder
     */
    public function run(): void
    {
        // Required dependency: roles must exist (created by DatabaseSeeder).
        // Bail with a clear message if someone runs this seeder before roles are seeded.
        if (Role::count() === 0) {
            $this->command->error('Roles table is empty. Run `php artisan db:seed` first to seed roles.');

            return;
        }

        $accounts = [
            [
                'username'  => 'admin',
                'email'     => 'admin@isms.local',
                'full_name' => 'System Administrator',
                'password'  => 'admin123',
                'role'      => 'Admin',
            ],
            [
                'username'  => 'manager',
                'email'     => 'manager@isms.local',
                'full_name' => 'Juan dela Cruz',
                'password'  => 'manager123',
                'role'      => 'Manager',
            ],
            [
                'username'  => 'cashier',
                'email'     => 'cashier@isms.local',
                'full_name' => 'Maria Santos',
                'password'  => 'cashier123',
                'role'      => 'Cashier',
            ],
        ];

        foreach ($accounts as $a) {
            $roleId = Role::where('role_name', $a['role'])->value('id');

            if (! $roleId) {
                $this->command->warn("Skipping {$a['username']}: role '{$a['role']}' not found.");
                continue;
            }

            User::firstOrCreate(
                ['username' => $a['username']],
                [
                    'email'     => $a['email'],
                    'full_name' => $a['full_name'],
                    'password'  => $a['password'],
                    'role_id'   => $roleId,
                    'status'    => 'active',
                ]
            );
        }

        $this->command->info('Seeded ' . User::count() . ' users.');
    }
}
