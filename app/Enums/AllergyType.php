<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AllergyType: string
{
    use EnumHelpers;

    case Drug = 'drug';
    case Food = 'food';
    case Environmental = 'environmental';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Drug => 'Drug',
            self::Food => 'Food',
            self::Environmental => 'Environmental',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Drug => 'red',
            self::Food => 'amber',
            self::Environmental => 'teal',
            self::Other => 'slate',
        };
    }
}
