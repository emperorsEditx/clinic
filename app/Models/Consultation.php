<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Enums\PrescriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    use HasFactory;

    /** Clinical fields that may be amended (with tracking) after completion. */
    public const CLINICAL_FIELDS = ['symptoms', 'clinical_observations', 'diagnosis', 'treatment_plan', 'doctor_notes', 'follow_up_instructions', 'follow_up_date'];

    protected $fillable = ['symptoms', 'clinical_observations', 'diagnosis', 'treatment_plan', 'doctor_notes', 'follow_up_instructions', 'follow_up_date'];

    protected function casts(): array
    {
        return [
            'status' => ConsultationStatus::class,
            'follow_up_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === ConsultationStatus::Draft;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id')->withTrashed();
    }

    public function vitals(): HasOne
    {
        return $this->hasOne(ConsultationVitals::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(ConsultationAmendment::class)->latest('id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /** The prescription currently in force for this consultation (latest non-superseded version). */
    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class)->ofMany(['id' => 'max'], function ($q) {
            $q->whereNotIn('status', [PrescriptionStatus::Superseded->value, PrescriptionStatus::Cancelled->value]);
        });
    }

    public function laboratoryRequests(): HasMany
    {
        return $this->hasMany(LaboratoryRequest::class);
    }

    public function followUp(): HasOne
    {
        return $this->hasOne(FollowUp::class);
    }
}
