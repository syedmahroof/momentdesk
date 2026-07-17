<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Traits\HasTenant;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'whatsapp_number',
        'source',
        'status',
        'follow_up_at',
        'notes',
        'created_by',
        'converted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'follow_up_at' => 'date',
            'converted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The customer this lead became, once converted.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * @param  Builder<Lead>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified]);
    }

    /**
     * @param  Builder<Lead>  $query
     */
    public function scopeDueForFollowUp(Builder $query): void
    {
        $query->open()->whereNotNull('follow_up_at')->whereDate('follow_up_at', '<=', today());
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    public function getContactNumberAttribute(): string
    {
        return $this->whatsapp_number ?? $this->phone ?? '';
    }
}
