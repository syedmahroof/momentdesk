<?php

namespace App\Enums;

/**
 * Where a lead came from. Exposed to the frontend via `LeadSource::options()`.
 */
enum LeadSource: string
{
    case WalkIn = 'walk_in';
    case Referral = 'referral';
    case Instagram = 'instagram';
    case Whatsapp = 'whatsapp';
    case PhoneCall = 'phone_call';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WalkIn => 'Walk-in',
            self::Referral => 'Referral',
            self::Instagram => 'Instagram',
            self::Whatsapp => 'WhatsApp',
            self::PhoneCall => 'Phone call',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $source) => ['value' => $source->value, 'label' => $source->label()],
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
