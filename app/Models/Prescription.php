<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = ['prescription_date', 'general_instructions', 'notes'];

    protected function casts(): array
    {
        return [
            'prescription_date' => 'date',
            'status' => PrescriptionStatus::class,
            'authorized_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === PrescriptionStatus::Draft;
    }

    public function isFinalized(): bool
    {
        return $this->status === PrescriptionStatus::Finalized;
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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id')->withTrashed();
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(MedicationCourse::class);
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function nextVersion(): HasOne
    {
        return $this->hasOne(self::class, 'previous_version_id');
    }

    public function repeatOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repeat_of_id');
    }

    public function repeats(): HasMany
    {
        return $this->hasMany(self::class, 'repeat_of_id');
    }
}
