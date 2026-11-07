<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MedicationCourseStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Completed = 'completed';
    case Discontinued = 'discontinued';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Discontinued => 'Discontinued',
            self::OnHold => 'On Hold',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Completed => 'slate',
            self::Discontinued => 'red',
            self::OnHold => 'amber',
        };
    }
}
