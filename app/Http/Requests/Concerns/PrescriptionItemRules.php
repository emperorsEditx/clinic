<?php

namespace App\Http\Requests\Concerns;

trait PrescriptionItemRules
{
    protected function itemRules(string $prefix = 'items', bool $required = false): array
    {
        return [
            $prefix => [$required ? 'required' : 'nullable', 'array', 'max:30'],
            "$prefix.*.medicine_id" => ['nullable', 'integer', 'exists:medicines,id'],
            "$prefix.*.medicine_name" => ['required', 'string', 'max:255'],
            "$prefix.*.generic_name" => ['nullable', 'string', 'max:255'],
            "$prefix.*.strength" => ['nullable', 'string', 'max:60'],
            "$prefix.*.dosage" => ['required', 'string', 'max:100'],
            "$prefix.*.route" => ['nullable', 'string', 'max:40'],
            "$prefix.*.frequency" => ['required', 'string', 'max:100'],
            "$prefix.*.timing" => ['nullable', 'string', 'max:100'],
            "$prefix.*.duration_days" => ['required', 'integer', 'min:1', 'max:365'],
            "$prefix.*.quantity" => ['nullable', 'string', 'max:60'],
            "$prefix.*.instructions" => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Drop completely blank rows the UI may submit. */
    protected function stripBlankItems(string $prefix = 'items'): void
    {
        $items = $this->input($prefix);
        if (is_array($items)) {
            $this->merge([$prefix => array_values(array_filter($items, fn ($i) => is_array($i) && array_filter($i, fn ($v) => $v !== null && $v !== '')))]);
        }
    }

    protected function itemAttributes(string $prefix = 'items'): array
    {
        return [
            "$prefix.*.medicine_name" => 'medicine name',
            "$prefix.*.dosage" => 'dosage',
            "$prefix.*.frequency" => 'frequency',
            "$prefix.*.duration_days" => 'duration (days)',
        ];
    }
}
