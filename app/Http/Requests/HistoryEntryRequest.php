<?php

namespace App\Http\Requests;

use App\Enums\AllergySeverity;
use App\Enums\AllergyType;
use App\Enums\ConditionCategory;
use App\Enums\ConditionStatus;
use App\Enums\PatientMedicationStatus;
use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistoryEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('medical_history.manage');
    }

    public function rules(): array
    {
        return match ($this->route('type')) {
            'condition' => [
                'category' => ['required', Rule::enum(ConditionCategory::class)],
                'name' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::enum(ConditionStatus::class)],
                'onset_date' => ['nullable', 'date', 'before_or_equal:today'],
                'resolved_date' => ['nullable', 'date', 'after_or_equal:onset_date', 'before_or_equal:today'],
                'relation' => ['nullable', 'string', 'max:60'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            'allergy' => [
                'allergen' => ['required', 'string', 'max:255'],
                'allergy_type' => ['required', Rule::enum(AllergyType::class)],
                'severity' => ['required', Rule::enum(AllergySeverity::class)],
                'reaction' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::enum(RecordStatus::class)],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            'medication' => [
                'medicine_name' => ['required', 'string', 'max:255'],
                'dosage' => ['nullable', 'string', 'max:255'],
                'frequency' => ['nullable', 'string', 'max:255'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'prescribing_source' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::enum(PatientMedicationStatus::class)],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            'note' => [
                'title' => ['nullable', 'string', 'max:255'],
                'notes' => ['required', 'string', 'max:5000'],
            ],
            default => abort(404),
        };
    }
}
