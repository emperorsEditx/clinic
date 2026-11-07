<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        return [
            'clinic_name' => ['required', 'string', 'max:255'],
            'clinic_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'clinic_address' => ['nullable', 'string', 'max:500'],
            'clinic_phone' => ['nullable', 'string', 'max:60'],
            'clinic_email' => ['nullable', 'email', 'max:255'],
            'clinic_website' => ['nullable', 'url', 'max:255'],
            'clinic_registration_number' => ['nullable', 'string', 'max:120'],
            'doctor_info' => ['nullable', 'string', 'max:1000'],
            'prescription_header' => ['nullable', 'string', 'max:1000'],
            'prescription_footer' => ['nullable', 'string', 'max:1000'],
            'patient_id_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'patient_id_format' => ['required', 'string', 'max:60', 'regex:/\{SEQ\}/'],
            'patient_id_padding' => ['required', 'integer', 'between:3,10'],
            'appointment_slot_minutes' => ['required', 'integer', Rule::in([5, 10, 15, 20, 30, 45, 60])],
            'appointment_day_start' => ['required', 'date_format:H:i'],
            'appointment_day_end' => ['required', 'date_format:H:i', 'after:appointment_day_start'],
            'appointment_types' => ['required', 'string', 'max:2000'],
            'timezone' => ['required', 'timezone:all'],
            'date_format' => ['required', Rule::in(array_keys(config('clinic.date_formats')))],
            'time_format' => ['required', Rule::in(['h:i A', 'H:i'])],
        ];
    }

    public function messages(): array
    {
        return ['patient_id_format.regex' => 'The format must contain {SEQ}.', 'patient_id_prefix.regex' => 'Use letters and numbers only.'];
    }
}
