<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosterCategory extends Model
{
    public const CUSTOM_SLUG = 'custom';

    protected $fillable = [
        'name',
        'slug',
        'order',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(PosterTemplate::class);
    }
}
