<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FlyerTemplate>
 */
class FlyerTemplateFactory extends Factory
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
            'title' => fake()->words(3, true),
            'category' => 'custom',
            'paper_size' => 'a4',
            'canvas_width' => 1240,
            'canvas_height' => 1754,
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'background_image_path' => null,
            'elements' => [
                [
                    'id' => 'el-1',
                    'type' => 'text',
                    'key' => 'title',
                    'label' => 'Title',
                    'content' => null,
                    'placeholder' => '{{title}}',
                    'x' => 90,
                    'y' => 200,
                    'width' => 1060,
                    'height' => null,
                    'font_size' => 48,
                    'color' => '#111827',
                    'alignment' => 'center',
                    'font_weight' => 'bold',
                ],
            ],
            'is_active' => true,
        ];
    }
}
