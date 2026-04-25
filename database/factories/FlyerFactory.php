<?php

namespace Database\Factories;

use App\Models\Flyer;
use App\Models\FlyerTemplate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flyer>
 */
class FlyerFactory extends Factory
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
            'flyer_template_id' => FlyerTemplate::factory(),
            'title' => fake()->sentence(3),
            'paper_size' => 'a4',
            'canvas_width' => 1240,
            'canvas_height' => 1754,
            'field_values' => ['title' => 'Sample'],
            'element_overrides' => [],
            'asset_paths' => [],
            'template_snapshot' => [
                'title' => 'Snapshot',
                'category' => 'custom',
                'paper_size' => 'a4',
                'canvas_width' => 1240,
                'canvas_height' => 1754,
                'background_type' => 'color',
                'background_color' => '#ffffff',
                'background_image_path' => null,
                'background_image_url' => null,
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
            ],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Flyer $flyer): void {
            $template = FlyerTemplate::withoutGlobalScopes()->find($flyer->flyer_template_id);

            if ($template && (int) $flyer->tenant_id !== (int) $template->tenant_id) {
                $flyer->forceFill(['tenant_id' => $template->tenant_id])->saveQuietly();
            }
        });
    }
}
