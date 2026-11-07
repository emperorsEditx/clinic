<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ConsultationStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Completed => 'green',
        };
    }
}
