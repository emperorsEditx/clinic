<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'doctor_id', 'appointment_id', 'visit_at', 'visit_type', 'chief_complaint', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'visit_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => VisitStatus::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id')->withTrashed();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function laboratoryReports(): HasMany
    {
        return $this->hasMany(LaboratoryReport::class);
    }

    public function visitTypeLabel(): string
    {
        return config('clinic.visit_types.'.$this->visit_type, ucfirst(str_replace('_', ' ', (string) $this->visit_type)));
    }
}
