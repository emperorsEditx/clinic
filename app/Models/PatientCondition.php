<?php

namespace App\Models;

use App\Enums\ConditionCategory;
use App\Enums\ConditionStatus;
use App\Models\Concerns\BelongsToPatientRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientCondition extends Model
{
    use BelongsToPatientRecord, HasFactory, SoftDeletes;

    protected $fillable = ['category', 'name', 'status', 'onset_date', 'resolved_date', 'relation', 'notes'];

    protected function casts(): array
    {
        return [
            'category' => ConditionCategory::class,
            'status' => ConditionStatus::class,
            'onset_date' => 'date',
            'resolved_date' => 'date',
        ];
    }
}
