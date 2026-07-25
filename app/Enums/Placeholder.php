<?php

namespace App\Enums;

/**
 * Canonical poster placeholder keys. Filled into poster {placeholders} by the
 * editor, the daily-rate update flow and per-customer posters. Exposed to the
 * frontend via `Placeholder::options()`.
 */
enum Placeholder: string
{
    case Date = 'date';
    case Status = 'status';
    case Price22k1g = 'price_22k_1g';
    case Price22k8g = 'price_22k_8g';
    case Price18k1g = 'price_18k_1g';
    case CustomerName = 'customer_name';
    case Message = 'message';
    case Age = 'age';

    public function label(): string
    {
        return match ($this) {
            self::Date => 'Date',
            self::Status => 'Status message',
            self::Price22k1g => '22K · 1 gram price',
            self::Price22k8g => '22K · 8 gram price',
            self::Price18k1g => '18K · 1 gram price',
            self::CustomerName => 'Customer name',
            self::Message => 'Message',
            self::Age => 'Age',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Date => 'common',
            self::Status, self::Price22k1g, self::Price22k8g, self::Price18k1g => 'gold_price',
            self::CustomerName, self::Message, self::Age => 'poster',
        };
    }

    /**
     * @return array<int, array{key: string, label: string, category: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $p) => ['key' => $p->value, 'label' => $p->label(), 'category' => $p->category()],
            self::cases(),
        );
    }
}
