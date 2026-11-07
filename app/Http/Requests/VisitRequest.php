<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('visits.manage');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctor_id' => ['required', 'integer', 'exists:users,id'],
            'visit_at' => ['nullable', 'date', 'before_or_equal:'.now()->addHour()->toDateTimeString()],
            'visit_type' => ['required', Rule::in(array_keys(config('clinic.visit_types')))],
            'chief_complaint' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
