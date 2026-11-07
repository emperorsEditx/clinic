<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationVitals extends Model
{
    protected $table = 'consultation_vitals';

    public const FIELDS = ['systolic_bp', 'diastolic_bp', 'temperature', 'pulse_rate', 'respiratory_rate', 'weight', 'height', 'oxygen_saturation'];

    protected $fillable = [...self::FIELDS, 'recorded_by'];

    protected function casts(): array
    {
        return ['temperature' => 'decimal:1', 'weight' => 'decimal:2', 'height' => 'decimal:1'];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function bloodPressure(): ?string
    {
        return $this->systolic_bp && $this->diastolic_bp ? $this->systolic_bp.'/'.$this->diastolic_bp.' mmHg' : null;
    }

    public function bmi(): ?float
    {
        if (! $this->weight || ! $this->height) {
            return null;
        }
        $m = (float) $this->height / 100;

        return $m > 0 ? round((float) $this->weight / ($m * $m), 1) : null;
    }

    public function isEmpty(): bool
    {
        foreach (self::FIELDS as $f) {
            if ($this->{$f} !== null) {
                return false;
            }
        }

        return true;
    }
}
