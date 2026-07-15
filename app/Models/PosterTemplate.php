<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class PosterTemplate extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'type',
        'document',
    ];

    protected function casts(): array
    {
        return [
            'document' => 'array',
        ];
    }
}
