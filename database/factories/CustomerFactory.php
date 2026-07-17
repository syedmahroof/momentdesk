<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => null,
            'name' => fake()->name(),
            'phone' => fake()->numerify('+91##########'),
            'email' => fake()->safeEmail(),
            'whatsapp_number' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
