<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('amend', $this->route('consultation'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'symptoms' => ['nullable', 'string', 'max:5000'],
            'clinical_observations' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            'treatment_plan' => ['nullable', 'string', 'max:5000'],
            'doctor_notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date'],
        ];
    }
}
