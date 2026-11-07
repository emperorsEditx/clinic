<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ConditionStatus: string
{
    use EnumHelpers;

    case Current = 'current';
    case Historical = 'historical';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Current',
            self::Historical => 'Historical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Current => 'red',
            self::Historical => 'slate',
        };
    }
}
