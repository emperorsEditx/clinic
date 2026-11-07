<?php

namespace App\Http\Requests;

use App\Models\LaboratoryReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabReportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LaboratoryReport::class);
    }

    public function rules(): array
    {
        $cfg = config('clinic.lab_uploads');
        $fromRequest = (bool) $this->route('laboratoryRequest');

        return [
            'patient_id' => [$fromRequest ? 'nullable' : 'required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'visit_id' => ['nullable', 'integer', Rule::exists('visits', 'id')->where('patient_id', $this->input('patient_id'))],
            'test_name' => [$fromRequest ? 'nullable' : 'required', 'string', 'max:255'],
            'test_category' => ['nullable', 'string', 'max:80'],
            'laboratory_name' => ['nullable', 'string', 'max:255'],
            'test_date' => ['nullable', 'date', 'before_or_equal:today'],
            'report_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:test_date'],
            'result_summary' => ['nullable', 'string', 'max:5000'],
            // Both extension and real content type (finfo) are checked.
            'file' => ['required', 'file', 'max:'.$cfg['max_kb'], 'mimes:'.implode(',', $cfg['mimes']), 'mimetypes:'.implode(',', $cfg['mimetypes'])],
        ];
    }
}
