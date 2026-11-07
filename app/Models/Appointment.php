<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time', 'appointment_type', 'reason', 'status', 'notes', 'cancellation_reason'];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'checked_in_at' => 'datetime',
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

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function scheduledAt(): Carbon
    {
        return $this->appointment_date->copy()->setTimeFromTimeString((string) $this->appointment_time);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true);
    }

    /** Allowed manual transitions (check-in happens through VisitService). */
    public function allowedTransitions(): array
    {
        return match ($this->status) {
            AppointmentStatus::Scheduled => [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow],
            AppointmentStatus::Confirmed => [AppointmentStatus::Scheduled, AppointmentStatus::Cancelled, AppointmentStatus::NoShow],
            AppointmentStatus::CheckedIn => [AppointmentStatus::Completed],
            AppointmentStatus::NoShow => [AppointmentStatus::Scheduled],
            default => [],
        };
    }
}
