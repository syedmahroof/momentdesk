<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerDate;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (no tenant)
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@momentdesk.com',
            'tenant_id' => null,
            'role' => 'super_admin',
        ]);

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

        // Default templates
        $this->seedTemplates($tenant->id);

        // Demo customers
        $this->seedCustomers($tenant->id, $admin->id);
    }

    private function seedTemplates(int $tenantId): void
    {
        $templates = [
            [
                'tenant_id' => $tenantId,
                'name' => 'Birthday Wish (WhatsApp)',
                'type' => 'birthday',
                'channel' => 'whatsapp',
                'content' => '🎂 Happy Birthday, {{customer_name}}! Wishing you a wonderful day filled with joy and love. May all your dreams come true!',
                'is_default' => true,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'Wedding Anniversary (WhatsApp)',
                'type' => 'wedding',
                'channel' => 'whatsapp',
                'content' => '💍 Happy {{ordinal_years}} Wedding Anniversary, {{customer_name}}! Wishing you many more years of love and happiness together.',
                'is_default' => true,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'Work Anniversary (WhatsApp)',
                'type' => 'work',
                'channel' => 'whatsapp',
                'content' => '🎉 Congratulations on your {{ordinal_years}} work anniversary, {{customer_name}}! Thank you for your dedication and hard work.',
                'is_default' => true,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'Birthday Wish (Email)',
                'type' => 'birthday',
                'channel' => 'email',
                'subject' => 'Happy Birthday, {{customer_name}}!',
                'content' => "Dear {{customer_name}},\n\nWishing you a very Happy Birthday! May this special day bring you joy, health, and happiness.\n\nWith warm regards,\nThe MomentDesk Team",
                'is_default' => false,
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }
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
