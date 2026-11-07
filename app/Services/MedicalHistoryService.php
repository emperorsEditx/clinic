<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientMedicalHistory;
use App\Models\PatientMedication;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Medical history is kept as discrete entries. Updates are diffed into the audit log and
 * removals are soft deletes, so nothing recorded is ever silently lost.
 */
class MedicalHistoryService
{
    public const TYPES = [
        'condition' => PatientCondition::class,
        'allergy' => PatientAllergy::class,
        'medication' => PatientMedication::class,
        'note' => PatientMedicalHistory::class,
    ];

    public function __construct(private AuditLogger $audit) {}

    public static function modelFor(string $type): string
    {
        return self::TYPES[$type] ?? throw new InvalidArgumentException("Unknown history type [$type]");
    }

    public function add(Patient $patient, string $type, array $data, User $by): Model
    {
        $class = self::modelFor($type);

        return DB::transaction(function () use ($class, $patient, $data, $by, $type) {
            /** @var Model $entry */
            $entry = new $class($data);
            $entry->patient_id = $patient->id;
            $entry->recorded_by = $by->id;
            $entry->updated_by = $by->id;
            $entry->save();
            $this->audit->created($entry, 'Added '.str_replace('_', ' ', $type).' to medical history');

            return $entry;
        });
    }

    public function update(Model $entry, array $data, User $by): Model
    {
        return DB::transaction(function () use ($entry, $data, $by) {
            $original = $entry->getOriginal();
            $entry->fill($data);
            $entry->updated_by = $by->id;
            $entry->save();
            $this->audit->updated($entry, $original, 'Updated medical history entry');

            return $entry;
        });
    }

    public function remove(Model $entry, string $reason): void
    {
        DB::transaction(function () use ($entry, $reason) {
            $entry->delete();
            $this->audit->log('removed', $entry, 'Removed medical history entry', ['reason' => $reason, 'attributes' => $entry->getAttributes()]);
        });
    }
}
