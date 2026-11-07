<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum Gender: string
{
    use EnumHelpers;

    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Male => 'slate',
            self::Female => 'slate',
            self::Other => 'slate',
        };
    }
}
