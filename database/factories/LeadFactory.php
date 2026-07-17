<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'name' => fake()->name(),
            'phone' => fake()->numerify('+91##########'),
            'email' => fake()->safeEmail(),
            'whatsapp_number' => null,
            'source' => fake()->randomElement(LeadSource::cases()),
            'status' => fake()->randomElement([LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified]),
            'follow_up_at' => fake()->optional()->dateTimeBetween('-1 week', '+2 weeks'),
            'notes' => fake()->optional()->sentence(),
            'converted_at' => null,
        ];
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LeadStatus::Won]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LeadStatus::Lost]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Won,
            'converted_at' => now(),
        ]);
    }

    public function dueForFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Contacted,
            'follow_up_at' => today()->subDay(),
        ]);
    }
}
