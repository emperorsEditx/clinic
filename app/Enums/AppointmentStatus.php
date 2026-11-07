<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AppointmentStatus: string
{
    use EnumHelpers;

    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Confirmed => 'Confirmed',
            self::CheckedIn => 'Checked In',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No Show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'blue',
            self::Confirmed => 'indigo',
            self::CheckedIn => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'slate',
            self::NoShow => 'red',
        };
    }
}
