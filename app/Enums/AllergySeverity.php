<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AllergySeverity: string
{
    use EnumHelpers;

    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Mild => 'Mild',
            self::Moderate => 'Moderate',
            self::Severe => 'Severe',
            self::Unknown => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Mild => 'amber',
            self::Moderate => 'amber',
            self::Severe => 'red',
            self::Unknown => 'slate',
        };
    }
}
