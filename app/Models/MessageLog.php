<?php

namespace App\Models;

use App\Traits\HasTenant;
use Database\Factories\MessageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    /** @use HasFactory<MessageLogFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'customer_date_id',
        'channel',
        'message',
        'recipient',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerDate(): BelongsTo
    {
        return $this->belongsTo(CustomerDate::class);
    }
}
