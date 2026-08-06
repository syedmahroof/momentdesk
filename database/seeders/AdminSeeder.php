<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed the super admin accounts for the admin panel.
     *
     * Uses firstOrCreate so re-running never overwrites the password of an
     * admin that already exists.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'superadmin@momentdesk.com');
        $password = env('ADMIN_PASSWORD', 'password');

        $admin = Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'password' => $password,
            ],
        );

        if ($admin->wasRecentlyCreated) {
            $this->command?->info("Admin created: {$admin->email}");

            return;
        }

        $this->command?->warn("Admin already exists, left untouched: {$admin->email}");
    }
}
