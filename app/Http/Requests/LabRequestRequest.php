<?php

namespace App\Http\Requests;

use App\Enums\LabPriority;
use App\Models\LaboratoryRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LaboratoryRequest::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'visit_id' => ['nullable', 'integer', Rule::exists('visits', 'id')->where('patient_id', $this->input('patient_id'))],
            'laboratory_test_id' => ['nullable', 'integer', 'exists:laboratory_tests,id'],
            'test_name' => ['nullable', 'required_without:laboratory_test_id', 'string', 'max:255'],
            'test_category' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', Rule::enum(LabPriority::class)],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
            'laboratory_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
