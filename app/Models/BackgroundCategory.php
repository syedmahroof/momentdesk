<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackgroundCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'order',
    ];

    public function backgrounds(): HasMany
    {
        return $this->hasMany(PosterBackground::class);
    }
}
