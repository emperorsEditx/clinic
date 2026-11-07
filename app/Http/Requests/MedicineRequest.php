<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('medicines.manage');
    }

    public function rules(): array
    {
        $id = $this->route('medicine')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('medicines')->where(fn ($q) => $q->where('strength', $this->input('strength'))->where('form', $this->input('form')))->ignore($id)],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:40'],
            'strength' => ['nullable', 'string', 'max:60'],
            'default_route' => ['nullable', 'string', 'max:40'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'This medicine (name, strength and form) already exists in the catalogue.'];
    }
}
