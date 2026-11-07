<?php

namespace App\Services;

use App\Exceptions\DuplicatePatientException;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function __construct(private SequenceGenerator $sequences, private AuditLogger $audit) {}

    /**
     * Likely duplicates: same phone (last 10 digits) with a similar name, or same name + DOB.
     *
     * @return Collection<int, Patient>
     */
    public function findPossibleDuplicates(array $data, ?int $ignoreId = null): Collection
    {
        $name = mb_strtolower(trim((string) ($data['full_name'] ?? '')));
        $phone = Patient::normalizePhone($data['phone'] ?? null);
        $dob = $data['date_of_birth'] ?? null;

        if ($name === '') {
            return collect();
        }

        return Patient::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(function ($q) use ($name, $phone, $dob) {
                if ($phone) {
                    $q->orWhere(fn ($w) => $w->where('phone_normalized', $phone)->whereRaw('LOWER(full_name) = ?', [$name]));
                }
                if ($dob) {
                    $q->orWhere(fn ($w) => $w->whereDate('date_of_birth', $dob)->whereRaw('LOWER(full_name) = ?', [$name]));
                }
                if (! $phone && ! $dob) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->limit(5)->get();
    }

    public function register(array $data, User $by, bool $allowDuplicate = false): Patient
    {
        if (! $allowDuplicate && ($dupes = $this->findPossibleDuplicates($data))->isNotEmpty()) {
            throw new DuplicatePatientException($dupes);
        }

        return DB::transaction(function () use ($data, $by) {
            $patient = new Patient($this->normalize($data));
            $patient->patient_code = $this->sequences->patientCode();
            $patient->registration_date ??= now()->toDateString();
            $patient->created_by = $by->id;
            $patient->updated_by = $by->id;
            $patient->save();

            $this->audit->created($patient, 'Registered patient '.$patient->patient_code);

            return $patient;
        });
    }

    public function update(Patient $patient, array $data, User $by): Patient
    {
        return DB::transaction(function () use ($patient, $data, $by) {
            $original = $patient->getOriginal();
            $patient->fill($this->normalize($data, $patient));
            $patient->updated_by = $by->id;
            $patient->save();
            $this->audit->updated($patient, $original, 'Updated patient details');

            return $patient;
        });
    }

    public function archive(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            $patient->delete();
            $this->audit->log('archived', $patient, 'Archived patient '.$patient->patient_code);
        });
    }

    public function restore(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            $patient->restore();
            $this->audit->log('restored', $patient, 'Restored patient '.$patient->patient_code);
        });
    }

    private function normalize(array $data, ?Patient $existing = null): array
    {
        $data['full_name'] = preg_replace('/\s+/', ' ', trim((string) ($data['full_name'] ?? $existing?->full_name)));
        if (! empty($data['date_of_birth'])) {
            $data['age'] = Carbon::parse($data['date_of_birth'])->age;
        }
        if (array_key_exists('age', $data) && (! $existing || (int) $existing->age !== (int) $data['age'])) {
            $data['age_recorded_on'] = now()->toDateString();
        }

        return $data;
    }
}
