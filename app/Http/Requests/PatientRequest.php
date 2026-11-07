<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient ? $this->user()->can('update', $patient) : $this->user()->can('create', Patient::class);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'age' => ['nullable', 'required_without:date_of_birth', 'integer', 'min:0', 'max:130'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today', 'after:1890-01-01'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'registration_date' => ['nullable', 'date', 'before_or_equal:today'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:60'],
            'blood_group' => ['nullable', Rule::in(config('clinic.blood_groups'))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['age.required_without' => 'Enter the age or the date of birth.', 'phone.regex' => 'Enter a valid phone number.'];
    }

    public function patientData(): array
    {
        return collect($this->validated())->except('confirm_duplicate')->all();
    }
}
