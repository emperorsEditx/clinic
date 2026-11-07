<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\VisitStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class VisitService
{
    public function __construct(private SequenceGenerator $sequences, private AuditLogger $audit) {}

    public function create(Patient $patient, array $data, User $by): Visit
    {
        $doctor = User::query()->active()->find($data['doctor_id'] ?? null);
        if (! $doctor || ! $doctor->isDoctor()) {
            throw new BusinessRuleException('Select an active doctor for this visit.');
        }

        return DB::transaction(function () use ($patient, $data, $by) {
            $visit = new Visit([
                'doctor_id' => $data['doctor_id'],
                'visit_at' => $data['visit_at'] ?? now(),
                'visit_type' => $data['visit_type'] ?? 'walk_in',
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $visit->patient_id = $patient->id;
            $visit->visit_number = $this->sequences->code('visit', 'VIS');
            $visit->status = VisitStatus::Waiting;
            $visit->created_by = $by->id;
            $visit->save();
            $this->audit->created($visit, 'Recorded visit '.$visit->visit_number);

            return $visit;
        });
    }

    /** Check in a booked appointment: marks it checked-in and opens a linked visit. */
    public function checkIn(Appointment $appointment, User $by, ?string $chiefComplaint = null): Visit
    {
        if (! $appointment->isOpen()) {
            throw new BusinessRuleException('Only scheduled or confirmed appointments can be checked in.');
        }

        return DB::transaction(function () use ($appointment, $by, $chiefComplaint) {
            $appointment->status = AppointmentStatus::CheckedIn;
            $appointment->checked_in_at = now();
            $appointment->updated_by = $by->id;
            $appointment->save();

            $visit = $this->create($appointment->patient, [
                'doctor_id' => $appointment->doctor_id,
                'visit_type' => 'appointment',
                'chief_complaint' => $chiefComplaint ?: $appointment->reason,
            ], $by);
            $visit->appointment_id = $appointment->id;
            $visit->save();

            $this->audit->log('checked_in', $appointment, 'Checked in appointment '.$appointment->appointment_number);

            return $visit;
        });
    }

    public function cancel(Visit $visit, User $by, string $reason): void
    {
        if ($visit->status !== VisitStatus::Waiting || $visit->consultations()->exists()) {
            throw new BusinessRuleException('Only waiting visits without consultations can be cancelled.');
        }
        DB::transaction(function () use ($visit, $reason) {
            $visit->status = VisitStatus::Cancelled;
            $visit->notes = trim($visit->notes."\nCancelled: ".$reason);
            $visit->save();
            if ($visit->appointment && $visit->appointment->status === AppointmentStatus::CheckedIn) {
                $visit->appointment->forceFill(['status' => AppointmentStatus::Cancelled, 'cancellation_reason' => $reason])->save();
            }
            $this->audit->log('cancelled', $visit, 'Cancelled visit', ['reason' => $reason]);
        });
    }

    public function complete(Visit $visit): void
    {
        $visit->status = VisitStatus::Completed;
        $visit->completed_at = now();
        $visit->save();

        if ($visit->appointment_id) {
            Appointment::query()->whereKey($visit->appointment_id)
                ->where('status', AppointmentStatus::CheckedIn->value)
                ->update(['status' => AppointmentStatus::Completed->value, 'updated_at' => now()]);
        }
    }
}
