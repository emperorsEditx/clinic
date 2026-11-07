<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ConditionCategory: string
{
    use EnumHelpers;

    case Condition = 'condition';
    case PreviousDisease = 'previous_disease';
    case Surgery = 'surgery';
    case FamilyHistory = 'family_history';

    public function label(): string
    {
        return match ($this) {
            self::Condition => 'Medical Condition',
            self::PreviousDisease => 'Previous Disease',
            self::Surgery => 'Previous Surgery',
            self::FamilyHistory => 'Family History',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Condition => 'blue',
            self::PreviousDisease => 'slate',
            self::Surgery => 'indigo',
            self::FamilyHistory => 'teal',
        };
    }
}
