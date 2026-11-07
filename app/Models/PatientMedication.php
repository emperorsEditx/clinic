<?php

namespace App\Models;

use App\Enums\PatientMedicationStatus;
use App\Models\Concerns\BelongsToPatientRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Medication from other sources / intercurrent remedies — never clinic-prescribed. */
class PatientMedication extends Model
{
    use BelongsToPatientRecord, HasFactory, SoftDeletes;

    protected $fillable = ['medicine_name', 'dosage', 'frequency', 'start_date', 'end_date', 'prescribing_source', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'status' => PatientMedicationStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
