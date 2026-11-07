<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum VisitStatus: string
{
    use EnumHelpers;

    case Waiting = 'waiting';
    case InConsultation = 'in_consultation';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::InConsultation => 'In Consultation',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'amber',
            self::InConsultation => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'slate',
        };
    }
}
