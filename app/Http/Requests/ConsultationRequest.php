<?php

namespace App\Http\Requests;

use App\Enums\LabPriority;
use App\Http\Requests\Concerns\PrescriptionItemRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultationRequest extends FormRequest
{
    use PrescriptionItemRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('consultation'));
    }

    protected function prepareForValidation(): void
    {
        $this->stripBlankItems('items');
        $labs = $this->input('lab_tests');
        if (is_array($labs)) {
            $this->merge(['lab_tests' => array_values(array_filter($labs, fn ($l) => filled($l['test_name'] ?? null) || filled($l['laboratory_test_id'] ?? null)))]);
        }
    }

    public function rules(): array
    {
        $completing = $this->input('action') === 'complete';

        return array_merge([
            'action' => ['required', Rule::in(['save', 'complete'])],
            'symptoms' => ['nullable', 'string', 'max:5000'],
            'clinical_observations' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => [$completing ? 'required' : 'nullable', 'string', 'max:5000'],
            'treatment_plan' => ['nullable', 'string', 'max:5000'],
            'doctor_notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date', 'after:today'],
            'vitals.systolic_bp' => ['nullable', 'integer', 'between:50,300'],
            'vitals.diastolic_bp' => ['nullable', 'integer', 'between:20,200', 'required_with:vitals.systolic_bp'],
            'vitals.temperature' => ['nullable', 'numeric', 'between:30,45'],
            'vitals.pulse_rate' => ['nullable', 'integer', 'between:20,250'],
            'vitals.respiratory_rate' => ['nullable', 'integer', 'between:4,80'],
            'vitals.weight' => ['nullable', 'numeric', 'between:0.3,400'],
            'vitals.height' => ['nullable', 'numeric', 'between:20,260'],
            'vitals.oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'general_instructions' => ['nullable', 'string', 'max:2000'],
            'prescription_notes' => ['nullable', 'string', 'max:2000'],
            'lab_tests' => ['nullable', 'array', 'max:20'],
            'lab_tests.*.laboratory_test_id' => ['nullable', 'integer', 'exists:laboratory_tests,id'],
            'lab_tests.*.test_name' => ['nullable', 'required_without:lab_tests.*.laboratory_test_id', 'string', 'max:255'],
            'lab_tests.*.priority' => ['nullable', Rule::enum(LabPriority::class)],
            'lab_tests.*.clinical_notes' => ['nullable', 'string', 'max:1000'],
        ], $this->itemRules('items'));
    }

    public function attributes(): array
    {
        return $this->itemAttributes('items') + ['vitals.systolic_bp' => 'systolic BP', 'vitals.diastolic_bp' => 'diastolic BP'];
    }
}
