<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PrescriptionStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Finalized = 'finalized';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Finalized => 'Finalized',
            self::Superseded => 'Superseded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Finalized => 'green',
            self::Superseded => 'slate',
            self::Cancelled => 'red',
        };
    }
}
