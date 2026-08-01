<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super admin for the admin panel — lives on the `admin` guard.
        $this->call(AdminSeeder::class);

        // Demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Agency',
            'slug' => 'demo-agency',
            'email' => 'demo@momentdesk.com',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@demo.com',
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        // Poster categories + starter gold-rate designs (Modern / Minimal / Advanced / Custom)
        $this->call(PosterCategorySeeder::class);

        // Jewellery background library (Bangle / Ring / Chain / …) for the poster editor
        $this->call(PosterBackgroundSeeder::class);

        // Demo customers
        $this->seedCustomers($tenant->id, $admin->id);
    }

    private function seedCustomers(int $tenantId, int $createdBy): void
    {
        $customers = [
            ['name' => 'Alice Johnson', 'phone' => '+1234567890', 'email' => 'alice@example.com', 'whatsapp_number' => '+1234567890'],
            ['name' => 'Bob Smith', 'phone' => '+0987654321', 'email' => 'bob@example.com', 'whatsapp_number' => '+0987654321'],
            ['name' => 'Carol Williams', 'phone' => '+1122334455', 'email' => 'carol@example.com', 'whatsapp_number' => '+1122334455'],
        ];

        foreach ($customers as $data) {
            $customer = Customer::create(array_merge($data, [
                'tenant_id' => $tenantId,
                'created_by' => $createdBy,
            ]));

            // Birthday
            CustomerDate::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'type' => 'birthday',
                'date' => now()->subYears(rand(25, 45))->month(now()->month)->day(rand(1, 28)),
                'reminder_days_before' => 1,
                'active' => true,
                'auto_send' => false,
            ]);

            // Wedding Anniversary (50% chance)
            if (rand(0, 1)) {
                CustomerDate::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customer->id,
                    'type' => 'wedding',
                    'date' => now()->subYears(rand(2, 15))->month(rand(1, 12))->day(rand(1, 28)),
                    'reminder_days_before' => 3,
                    'active' => true,
                    'auto_send' => false,
                ]);
            }
        }
    }
}
