<?php

namespace App\Models;

use App\Enums\MedicationCourseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationCourse extends Model
{
    protected $fillable = ['patient_id', 'prescription_id', 'prescription_item_id', 'medicine_name', 'start_date', 'duration_days', 'expected_end_date', 'continuation_instructions', 'status', 'status_reason'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'status' => MedicationCourseStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class, 'prescription_item_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by')->withTrashed();
    }

    public function daysRemaining(): ?int
    {
        if ($this->status !== MedicationCourseStatus::Active) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->expected_end_date, false));
    }
}
