<?php

namespace App\Models;

use App\Enums\AllergySeverity;
use App\Enums\AllergyType;
use App\Enums\RecordStatus;
use App\Models\Concerns\BelongsToPatientRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientAllergy extends Model
{
    use BelongsToPatientRecord, HasFactory, SoftDeletes;

    protected $fillable = ['allergen', 'allergy_type', 'severity', 'reaction', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'allergy_type' => AllergyType::class,
            'severity' => AllergySeverity::class,
            'status' => RecordStatus::class,
        ];
    }
}
