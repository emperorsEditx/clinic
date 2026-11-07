<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(private SequenceGenerator $sequences, private AuditLogger $audit) {}

    public function create(array $data, User $by): Appointment
    {
        $this->assertDoctor((int) $data['doctor_id']);
        $this->assertSlotFree($data);

        return DB::transaction(function () use ($data, $by) {
            $appointment = new Appointment($data);
            $appointment->appointment_number = $this->sequences->code('appointment', 'APT');
            $appointment->status = AppointmentStatus::Scheduled;
            $appointment->created_by = $by->id;
            $appointment->updated_by = $by->id;
            $appointment->save();
            $this->audit->created($appointment, 'Booked appointment '.$appointment->appointment_number);

            return $appointment;
        });
    }

    public function update(Appointment $appointment, array $data, User $by): Appointment
    {
        if (! $appointment->isOpen()) {
            throw new BusinessRuleException('Only scheduled or confirmed appointments can be rescheduled.');
        }
        $this->assertDoctor((int) $data['doctor_id']);
        $this->assertSlotFree($data, $appointment->id);

        return DB::transaction(function () use ($appointment, $data, $by) {
            $original = $appointment->getOriginal();
            $appointment->fill($data);
            $appointment->updated_by = $by->id;
            $appointment->save();
            $this->audit->updated($appointment, $original, 'Updated appointment');

            return $appointment;
        });
    }

    public function changeStatus(Appointment $appointment, AppointmentStatus $to, User $by, ?string $reason = null): Appointment
    {
        if (! in_array($to, $appointment->allowedTransitions(), true)) {
            throw new BusinessRuleException("Cannot change appointment from {$appointment->status->label()} to {$to->label()}.");
        }
        if ($to === AppointmentStatus::Cancelled && blank($reason)) {
            throw new BusinessRuleException('A cancellation reason is required.');
        }

        return DB::transaction(function () use ($appointment, $to, $by, $reason) {
            $from = $appointment->status;
            $appointment->status = $to;
            $appointment->cancellation_reason = $to === AppointmentStatus::Cancelled ? $reason : $appointment->cancellation_reason;
            $appointment->updated_by = $by->id;
            $appointment->save();
            $this->audit->log('status_changed', $appointment, "Appointment {$from->label()} → {$to->label()}", ['status' => ['old' => $from->value, 'new' => $to->value], 'reason' => $reason]);

            return $appointment;
        });
    }

    private function assertDoctor(int $doctorId): void
    {
        $doctor = User::query()->active()->find($doctorId);
        if (! $doctor || ! $doctor->isDoctor()) {
            throw new BusinessRuleException('The selected doctor is not an active doctor.');
        }
    }

    private function assertSlotFree(array $data, ?int $ignoreId = null): void
    {
        $time = substr((string) $data['appointment_time'], 0, 5);
        $clash = Appointment::query()
            ->where('doctor_id', $data['doctor_id'])
            ->whereDate('appointment_date', $data['appointment_date'])
            ->where('appointment_time', 'like', $time.'%')
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::NoShow->value])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($clash) {
            throw new BusinessRuleException('The doctor already has an appointment at that time.');
        }
    }
}
