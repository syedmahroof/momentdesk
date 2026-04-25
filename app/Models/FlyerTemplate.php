<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlyerTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\FlyerTemplateFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'category',
        'paper_size',
        'canvas_width',
        'canvas_height',
        'background_type',
        'background_color',
        'background_image_path',
        'elements',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'elements' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class);
    }
}
