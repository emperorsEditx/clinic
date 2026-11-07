<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplate extends Model
{
    protected $fillable = ['name', 'doctor_id', 'is_shared', 'general_instructions', 'items'];

    protected function casts(): array
    {
        return ['items' => 'array', 'is_shared' => 'boolean'];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id')->withTrashed();
    }

    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $q->where(fn ($w) => $w->where('doctor_id', $user->id)->orWhere('is_shared', true));
    }
}
