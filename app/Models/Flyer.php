<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flyer extends Model
{
    /** @use HasFactory<\Database\Factories\FlyerFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'flyer_template_id',
        'title',
        'paper_size',
        'canvas_width',
        'canvas_height',
        'field_values',
        'element_overrides',
        'asset_paths',
        'template_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'field_values' => 'array',
            'element_overrides' => 'array',
            'asset_paths' => 'array',
            'template_snapshot' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function flyerTemplate(): BelongsTo
    {
        return $this->belongsTo(FlyerTemplate::class);
    }
}
