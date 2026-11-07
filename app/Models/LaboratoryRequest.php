<?php

namespace App\Models;

use App\Enums\LabPriority;
use App\Enums\LabRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaboratoryRequest extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'visit_id', 'consultation_id', 'laboratory_test_id', 'test_name', 'test_category', 'priority', 'clinical_notes', 'laboratory_name'];

    protected function casts(): array
    {
        return [
            'status' => LabRequestStatus::class,
            'priority' => LabPriority::class,
            'sample_collected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LaboratoryTest::class, 'laboratory_test_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(LaboratoryReport::class);
    }

    /** Status transitions a lab technician may apply manually. Completion happens on upload; review by doctors. */
    public function allowedManualTransitions(): array
    {
        return match ($this->status) {
            LabRequestStatus::Requested => [LabRequestStatus::SampleCollected, LabRequestStatus::Processing, LabRequestStatus::Cancelled],
            LabRequestStatus::SampleCollected => [LabRequestStatus::Processing, LabRequestStatus::Cancelled],
            LabRequestStatus::Processing => [LabRequestStatus::SampleCollected],
            default => [],
        };
    }
}
