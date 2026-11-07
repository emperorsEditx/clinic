<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PrescriptionItemRules;
use Illuminate\Foundation\Http\FormRequest;

class PrescriptionRequest extends FormRequest
{
    use PrescriptionItemRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('prescription'));
    }

    protected function prepareForValidation(): void
    {
        $this->stripBlankItems('items');
    }

    public function rules(): array
    {
        return array_merge([
            'general_instructions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'finalize' => ['nullable', 'boolean'],
        ], $this->itemRules('items', true));
    }

    public function attributes(): array
    {
        return $this->itemAttributes('items');
    }
}
