<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum LabRequestStatus: string
{
    use EnumHelpers;

    case Requested = 'requested';
    case SampleCollected = 'sample_collected';
    case Processing = 'processing';
    case Completed = 'completed';
    case Reviewed = 'reviewed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::SampleCollected => 'Sample Collected',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Reviewed => 'Reviewed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'blue',
            self::SampleCollected => 'indigo',
            self::Processing => 'amber',
            self::Completed => 'green',
            self::Reviewed => 'teal',
            self::Cancelled => 'slate',
        };
    }
}
