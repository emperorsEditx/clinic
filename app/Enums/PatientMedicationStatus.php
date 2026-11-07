<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PatientMedicationStatus: string
{
    use EnumHelpers;

    case Current = 'current';
    case Previous = 'previous';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Current',
            self::Previous => 'Previous',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Current => 'green',
            self::Previous => 'slate',
        };
    }
}
