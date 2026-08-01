<?php

namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosterTemplate extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'type',
        'poster_category_id',
        'document',
    ];

    public function posterCategory(): BelongsTo
    {
        return $this->belongsTo(PosterCategory::class);
    }

    /**
     * Route-bound lookups bypass the tenant scope so admin-seeded, tenant_id-null
     * "global" starter designs can be resolved too — controllers authorize access explicitly.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return static::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'document' => 'array',
        ];
    }
}
