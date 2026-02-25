<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDate extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerDateFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'type',
        'title',
        'date',
        'reminder_days_before',
        'active',
        'auto_send',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'active' => 'boolean',
            'auto_send' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function getYearsAttribute(): int
    {
        return (int) $this->date->diffInYears(now());
    }

    public function getOrdinalYearsAttribute(): string
    {
        $years = $this->years;

        if ($years === 0) {
            return 'First';
        }

        $suffix = ['th', 'st', 'nd', 'rd'];
        $v = $years % 100;

        return $years . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
    }

    public function getDisplayTitleAttribute(): string
    {
        return match ($this->type) {
            'birthday' => 'Birthday',
            'wedding' => "{$this->ordinal_years} Wedding Anniversary",
            'work' => "{$this->ordinal_years} Work Anniversary",
            default => $this->title ?? 'Special Day',
        };
    }

    public function getNextOccurrenceAttribute(): CarbonInterface
    {
        $today = now()->startOfDay();
        $next = Carbon::create(
            $today->year,
            $this->date->month,
            $this->date->day,
        )->startOfDay();

        if ($next->lt($today)) {
            $next->addYear();
        }

        return $next;
    }

    public function getDaysUntilAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->next_occurrence, false);
    }

    public function scopeUpcoming(Builder $query, int $days = 7): Builder
    {
        return $query->where('active', true)->whereRaw(
            "DATE_FORMAT(date, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ? DAY), '%m-%d')",
            [$days]
        );
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('active', true)->whereRaw(
            "DATE_FORMAT(date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')"
        );
    }
}
