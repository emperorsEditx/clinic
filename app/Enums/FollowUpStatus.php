<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum FollowUpStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Completed = 'completed';
    case Missed = 'missed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Missed => 'Missed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Completed => 'green',
            self::Missed => 'red',
            self::Cancelled => 'slate',
        };
    }
}
