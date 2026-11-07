<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationRepetition extends Model
{
    protected $fillable = ['patient_id', 'source_prescription_id', 'source_item_id', 'new_prescription_id', 'new_item_id', 'reason', 'authorized_by'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function sourcePrescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'source_prescription_id');
    }

    public function newPrescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'new_prescription_id');
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class, 'source_item_id');
    }

    public function newItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class, 'new_item_id');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by')->withTrashed();
    }
}
