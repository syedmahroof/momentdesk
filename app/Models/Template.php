<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    /** @use HasFactory<\Database\Factories\TemplateFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'channel',
        'subject',
        'content',
        'variables',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function renderFor(Customer $customer, CustomerDate $date): string
    {
        return str_replace(
            ['{{customer_name}}', '{{event_name}}', '{{years}}', '{{ordinal_years}}'],
            [$customer->name, $date->display_title, (string) $date->years, $date->ordinal_years],
            $this->content
        );
    }
}
