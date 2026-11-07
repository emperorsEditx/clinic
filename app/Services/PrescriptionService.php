<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Enums\MedicationCourseStatus;
use App\Enums\PrescriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Consultation;
use App\Models\MedicationCourse;
use App\Models\MedicationRepetition;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Prescriptions are editable only while draft. Finalised prescriptions are immutable:
 * changes create a new version (amendment) and the old one becomes "superseded".
 */
class PrescriptionService
{
    public function __construct(private SequenceGenerator $sequences, private AuditLogger $audit) {}

    public function draftFor(Consultation $consultation, User $doctor): Prescription
    {
        $existing = $consultation->prescriptions()->where('status', PrescriptionStatus::Draft->value)->latest('id')->first();
        if ($existing) {
            return $existing;
        }

        $rx = new Prescription(['prescription_date' => now()->toDateString()]);
        $rx->forceFill([
            'prescription_number' => $this->sequences->code('prescription', 'RX'),
            'patient_id' => $consultation->patient_id,
            'visit_id' => $consultation->visit_id,
            'consultation_id' => $consultation->id,
            'doctor_id' => $doctor->id,
            'status' => PrescriptionStatus::Draft,
            'created_by' => $doctor->id,
        ])->save();

        return $rx;
    }

    /**
     * Replace the items of a DRAFT prescription.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function syncItems(Prescription $rx, array $items, array $header = []): Prescription
    {
        if (! $rx->isDraft()) {
            throw new BusinessRuleException('Finalised prescriptions cannot be edited. Create an amendment instead.');
        }

        return DB::transaction(function () use ($rx, $items, $header) {
            $rx->fill(array_intersect_key($header, array_flip(['general_instructions', 'notes', 'prescription_date'])));
            $rx->save();

            // Draft items are working data (never finalised) — safe to replace.
            $rx->items()->delete();
            ksort($items); // keep the submitted row order (see ConsultationService::save)
            foreach (array_values($items) as $i => $item) {
                $rx->items()->create(array_merge(
                    array_intersect_key($item, array_flip(PrescriptionItem::FIELDS)),
                    ['sort_order' => $i],
                ));
            }

            return $rx->load('items');
        });
    }

    public function finalize(Prescription $rx, User $doctor): Prescription
    {
        if (! $rx->isDraft()) {
            throw new BusinessRuleException('Only draft prescriptions can be finalised.');
        }
        $rx->loadMissing('items', 'consultation');
        if ($rx->items->isEmpty()) {
            throw new BusinessRuleException('A prescription needs at least one medicine before it can be finalised.');
        }
        if ($rx->consultation->status !== ConsultationStatus::Completed && ! $rx->previous_version_id && ! $rx->repeat_of_id) {
            // Regular prescriptions are finalised together with their consultation.
            throw new BusinessRuleException('Complete the consultation to finalise its prescription.');
        }

        return DB::transaction(function () use ($rx, $doctor) {
            $rx->status = PrescriptionStatus::Finalized;
            $rx->finalized_at = now();
            $rx->save();

            if ($rx->previous_version_id) {
                $previous = Prescription::query()->lockForUpdate()->findOrFail($rx->previous_version_id);
                $previous->status = PrescriptionStatus::Superseded;
                $previous->save();
                $previous->courses()->where('status', '!=', MedicationCourseStatus::Completed->value)->get()
                    ->each(fn (MedicationCourse $c) => $c->forceFill([
                        'status' => MedicationCourseStatus::Discontinued,
                        'status_reason' => 'Superseded by '.$rx->prescription_number,
                        'status_changed_at' => now(),
                        'status_changed_by' => $doctor->id,
                    ])->save());
            }

            $this->createCourses($rx);
            $this->audit->log('finalized', $rx, 'Finalised prescription '.$rx->prescription_number, [
                'version' => $rx->version,
                'items' => $rx->items->map->toTemplateArray()->all(),
            ]);

            return $rx;
        });
    }

    /** Start an amendment: a new draft version copying the finalised one. */
    public function amend(Prescription $rx, User $doctor, string $reason): Prescription
    {
        if (! $rx->isFinalized()) {
            throw new BusinessRuleException('Only the current finalised version of a prescription can be amended.');
        }
        if ($rx->nextVersion()->exists()) {
            throw new BusinessRuleException('An amendment for this prescription is already in progress.');
        }

        return DB::transaction(function () use ($rx, $doctor, $reason) {
            $new = $rx->replicate(['prescription_number', 'status', 'finalized_at', 'version', 'previous_version_id', 'amendment_reason', 'created_at', 'updated_at']);
            $new->forceFill([
                'prescription_number' => $rx->prescription_number.'-A'.$rx->version,
                'status' => PrescriptionStatus::Draft,
                'version' => $rx->version + 1,
                'previous_version_id' => $rx->id,
                'amendment_reason' => $reason,
                'doctor_id' => $doctor->id,
                'created_by' => $doctor->id,
                'prescription_date' => now()->toDateString(),
            ])->save();
            foreach ($rx->items as $item) {
                $new->items()->create($item->only([...PrescriptionItem::FIELDS, 'sort_order']));
            }
            $this->audit->log('amendment_started', $new, 'Started amendment of '.$rx->prescription_number, ['reason' => $reason, 'previous_id' => $rx->id]);

            return $new;
        });
    }

    /** Discard a draft that was never finalised (amendment or repeat drafts, or an unfinished consultation draft). */
    public function discardDraft(Prescription $rx): void
    {
        if (! $rx->isDraft()) {
            throw new BusinessRuleException('Only draft prescriptions can be discarded.');
        }
        DB::transaction(function () use ($rx) {
            MedicationRepetition::query()->where('new_prescription_id', $rx->id)->delete();
            $rx->items()->delete();
            $this->audit->log('draft_discarded', $rx, 'Discarded draft '.$rx->prescription_number);
            $rx->delete();
        });
    }

    /**
     * Authorised repeat: copies selected items of a finalised prescription into a new prescription
     * attached to the given consultation, recording reason, authoriser and item-level links.
     *
     * @param  array<int, array<string, mixed>>  $items  keyed by source item id; values may override dosage/duration/etc.
     */
    public function repeat(Prescription $source, Consultation $consultation, array $items, string $reason, User $doctor, bool $finalize = true): Prescription
    {
        if (! $doctor->can('prescriptions.repeat')) {
            throw new BusinessRuleException('Only authorised doctors can approve repeat prescriptions.');
        }
        if (! in_array($source->status, [PrescriptionStatus::Finalized, PrescriptionStatus::Superseded], true)) {
            throw new BusinessRuleException('Only finalised prescriptions can be repeated.');
        }
        if ($consultation->patient_id !== $source->patient_id) {
            throw new BusinessRuleException('The consultation belongs to a different patient.');
        }
        $sourceItems = $source->items()->whereIn('id', array_keys($items))->get()->keyBy('id');
        if ($sourceItems->isEmpty()) {
            throw new BusinessRuleException('Select at least one medicine to repeat.');
        }

        return DB::transaction(function () use ($source, $consultation, $items, $reason, $doctor, $finalize, $sourceItems) {
            $rx = new Prescription([
                'prescription_date' => now()->toDateString(),
                'general_instructions' => $source->general_instructions,
            ]);
            $rx->forceFill([
                'prescription_number' => $this->sequences->code('prescription', 'RX'),
                'patient_id' => $consultation->patient_id,
                'visit_id' => $consultation->visit_id,
                'consultation_id' => $consultation->id,
                'doctor_id' => $doctor->id,
                'status' => PrescriptionStatus::Draft,
                'repeat_of_id' => $source->id,
                'repeat_reason' => $reason,
                'authorized_by' => $doctor->id,
                'authorized_at' => now(),
                'created_by' => $doctor->id,
            ])->save();

            $order = 0;
            foreach ($sourceItems as $id => $sourceItem) {
                $overrides = array_intersect_key(array_filter($items[$id] ?? [], fn ($v) => $v !== null && $v !== ''), array_flip(PrescriptionItem::FIELDS));
                $newItem = $rx->items()->create(array_merge($sourceItem->only(PrescriptionItem::FIELDS), $overrides, ['sort_order' => $order++]));
                MedicationRepetition::query()->create([
                    'patient_id' => $rx->patient_id,
                    'source_prescription_id' => $source->id,
                    'source_item_id' => $sourceItem->id,
                    'new_prescription_id' => $rx->id,
                    'new_item_id' => $newItem->id,
                    'reason' => $reason,
                    'authorized_by' => $doctor->id,
                ]);
            }

            $this->audit->log('repeat_authorized', $rx, 'Authorised repeat of '.$source->prescription_number, [
                'source_prescription_id' => $source->id,
                'reason' => $reason,
                'items' => $sourceItems->keys()->all(),
            ]);

            return $finalize ? $this->finalize($rx->fresh(), $doctor) : $rx;
        });
    }

    public static function expectedEndDate(Carbon|string $start, int $durationDays): Carbon
    {
        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);

        // A 5-day course started on day 1 ends on day 5.
        return $start->startOfDay()->addDays(max(1, $durationDays) - 1);
    }

    private function createCourses(Prescription $rx): void
    {
        $start = $rx->prescription_date ?? now();
        foreach ($rx->items as $item) {
            MedicationCourse::query()->create([
                'patient_id' => $rx->patient_id,
                'prescription_id' => $rx->id,
                'prescription_item_id' => $item->id,
                'medicine_name' => trim($item->medicine_name.' '.$item->strength),
                'start_date' => $start->toDateString(),
                'duration_days' => $item->duration_days,
                'expected_end_date' => self::expectedEndDate($start, $item->duration_days)->toDateString(),
                'continuation_instructions' => collect([$item->dosage, $item->frequency, $item->timing, $item->instructions])->filter()->implode(' · '),
                'status' => MedicationCourseStatus::Active,
            ]);
        }
    }
}
