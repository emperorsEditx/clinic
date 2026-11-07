<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name', 'age', 'age_recorded_on', 'date_of_birth', 'gender', 'phone', 'email', 'address',
        'registration_date', 'emergency_contact_name', 'emergency_contact_phone',
        'emergency_contact_relation', 'blood_group', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'age_recorded_on' => 'date',
            'registration_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Patient $p) {
            $p->phone_normalized = self::normalizePhone($p->phone);
        });
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits === '' ? null : substr($digits, -10);
    }

    /** Current age: from DOB when known, otherwise the recorded age advanced by elapsed years. */
    public function currentAge(): ?int
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age;
        }
        if ($this->age === null) {
            return null;
        }
        $since = $this->age_recorded_on ?? $this->registration_date;

        return $since ? $this->age + (int) $since->diffInYears(now()) : $this->age;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $digits = preg_replace('/\D+/', '', $term);

        return $q->where(function (Builder $w) use ($term, $digits) {
            $w->where('patient_code', 'like', $term.'%')
                ->orWhere('patient_code', 'like', '%'.$term)
                ->orWhere('full_name', 'like', '%'.$term.'%');
            if (strlen($digits) >= 4) {
                $w->orWhere('phone_normalized', 'like', '%'.$digits.'%');
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(PatientMedicalHistory::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientCondition::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function otherMedications(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function medicationCourses(): HasMany
    {
        return $this->hasMany(MedicationCourse::class);
    }

    public function medicationRepetitions(): HasMany
    {
        return $this->hasMany(MedicationRepetition::class);
    }

    public function laboratoryRequests(): HasMany
    {
        return $this->hasMany(LaboratoryRequest::class);
    }

    public function laboratoryReports(): HasMany
    {
        return $this->hasMany(LaboratoryReport::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
