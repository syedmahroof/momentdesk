<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class GoldRate extends Model
{
    use HasTenant;

    protected $fillable = [
        'tenant_id',
        'rate_date',
        'price_22k_1g',
        'price_22k_8g',
        'price_18k_1g',
    ];

    protected function casts(): array
    {
        return [
            'rate_date' => 'date:Y-m-d',
            'price_22k_1g' => 'integer',
            'price_22k_8g' => 'integer',
            'price_18k_1g' => 'integer',
        ];
    }
}
