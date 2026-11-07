<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Writes the clinical/administrative audit trail. Never stores secrets.
 */
class AuditLogger
{
    /** Attributes that must never reach the audit log. */
    private const REDACT = ['password', 'remember_token', 'token', 'api_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    /** Attributes that are noise in a diff. */
    private const IGNORE = ['updated_at', 'created_at', 'phone_normalized'];

    public function log(string $action, ?Model $entity = null, ?string $description = null, array $changes = [], ?int $patientId = null): ActivityLog
    {
        $request = app()->runningInConsole() ? null : request();

        return ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entity ? $entity->getMorphClass() : null,
            'entity_id' => $entity?->getKey(),
            'patient_id' => $patientId ?? $this->resolvePatientId($entity),
            'description' => $description,
            'changes' => $this->sanitize($changes) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }

    public function created(Model $entity, ?string $description = null): ActivityLog
    {
        $attrs = Arr::except($entity->getAttributes(), array_merge(self::IGNORE, ['id']));

        return $this->log('created', $entity, $description, ['attributes' => $attrs]);
    }

    /** Call BEFORE save() with the dirty model, or pass the original array explicitly. */
    public function updated(Model $entity, array $original, ?string $description = null): ?ActivityLog
    {
        $diff = [];
        foreach ($entity->getChanges() ?: $entity->getDirty() as $key => $new) {
            if (in_array($key, self::IGNORE, true)) {
                continue;
            }
            $old = $this->scalar($original[$key] ?? null);
            $new = $this->scalar($new);
            if ((string) $old === (string) $new) {
                continue;
            }
            $diff[$key] = ['old' => $old, 'new' => $new];
        }

        return $diff ? $this->log('updated', $entity, $description, $diff) : null;
    }

    /** Normalise cast values (enums, dates) so originals compare against raw attributes. */
    private function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value) => (int) $value,
            default => $value,
        };
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::REDACT, true)) {
                unset($data[$key]);

                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    private function resolvePatientId(?Model $entity): ?int
    {
        if (! $entity) {
            return null;
        }
        if ($entity instanceof Patient) {
            return $entity->getKey();
        }

        return $entity->getAttribute('patient_id');
    }
}
