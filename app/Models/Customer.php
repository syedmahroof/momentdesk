<?php

namespace App\Models;

use App\Traits\HasTenant;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'name',
        'phone',
        'email',
        'whatsapp_number',
        'notes',
        'created_by',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The lead this customer was converted from, when they were not added directly.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(CustomerDate::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function getContactNumberAttribute(): string
    {
        return $this->whatsapp_number ?? $this->phone ?? '';
    }
}
