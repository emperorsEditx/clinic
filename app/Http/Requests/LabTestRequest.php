<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('lab_tests.manage');
    }

    public function rules(): array
    {
        $id = $this->route('laboratoryTest')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('laboratory_tests')->where('category', $this->input('category'))->ignore($id)],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('laboratory_tests', 'code')->ignore($id)],
            'category' => ['required', 'string', 'max:80'],
            'sample_type' => ['nullable', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
