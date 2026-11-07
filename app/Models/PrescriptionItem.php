<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PrescriptionItem extends Model
{
    public const FIELDS = ['medicine_id', 'medicine_name', 'generic_name', 'strength', 'dosage', 'route', 'frequency', 'timing', 'duration_days', 'quantity', 'instructions'];

    protected $fillable = [...self::FIELDS, 'sort_order'];

    protected function casts(): array
    {
        return ['duration_days' => 'integer'];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function course(): HasOne
    {
        return $this->hasOne(MedicationCourse::class);
    }

    public function toTemplateArray(): array
    {
        return $this->only(self::FIELDS);
    }
}
