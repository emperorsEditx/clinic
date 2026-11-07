<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('appointments.manage');
    }

    public function rules(): array
    {
        $creating = ! $this->route('appointment');

        return [
            'patient_id' => [$creating ? 'required' : 'prohibited', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctor_id' => ['required', 'integer', 'exists:users,id'],
            'appointment_date' => ['required', 'date', $creating ? 'after_or_equal:today' : 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'appointment_type' => ['required', 'string', Rule::in(settings()->appointmentTypes())],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
