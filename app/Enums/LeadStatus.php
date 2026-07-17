<?php

namespace App\Enums;

/**
 * Pipeline stages a lead moves through. `Won` is the only stage a lead can be
 * converted into a customer from; conversion is handled by
 * `LeadController::convert()`. Exposed to the frontend via `LeadStatus::options()`.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    /**
     * Tailwind classes used for the status badge in the leads list.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
            self::Contacted => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            self::Qualified => 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
            self::Won => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            self::Lost => 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
        };
    }

    public function isOpen(): bool
    {
        return match ($this) {
            self::New, self::Contacted, self::Qualified => true,
            self::Won, self::Lost => false,
        };
    }

    /**
     * @return array<int, array{value: string, label: string, badge_classes: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'badge_classes' => $status->badgeClasses(),
            ],
            self::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
