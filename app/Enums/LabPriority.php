<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum LabPriority: string
{
    use EnumHelpers;

    case Routine = 'routine';
    case Urgent = 'urgent';
    case Stat = 'stat';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Urgent => 'Urgent',
            self::Stat => 'STAT',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Routine => 'slate',
            self::Urgent => 'amber',
            self::Stat => 'red',
        };
    }
}
