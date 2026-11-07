<?php

namespace App\Http\Requests;

use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepeatPrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('repeat', [Prescription::class, $this->route('patient')]);
    }

    public function rules(): array
    {
        $patientId = $this->route('patient')->id;

        return [
            'source_prescription_id' => ['required', 'integer', Rule::exists('prescriptions', 'id')->where('patient_id', $patientId)],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
            'consultation_id' => ['nullable', 'integer', Rule::exists('consultations', 'id')->where('patient_id', $patientId)->where('status', 'draft')],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
            'items' => ['nullable', 'array'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.timing' => ['nullable', 'string', 'max:100'],
            'items.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'items.*.quantity' => ['nullable', 'string', 'max:60'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['selected.required' => 'Select at least one medicine to repeat.'];
    }
}
