<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum LabReportStatus: string
{
    use EnumHelpers;

    case Completed = 'completed';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Awaiting Review',
            self::Reviewed => 'Reviewed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'amber',
            self::Reviewed => 'teal',
        };
    }
}
