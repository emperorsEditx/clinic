<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'generic_name', 'form', 'strength', 'default_route', 'manufacturer', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $term === '' ? $q : $q->where(fn ($w) => $w->where('name', 'like', '%'.$term.'%')->orWhere('generic_name', 'like', '%'.$term.'%'));
    }

    public function label(): string
    {
        return trim($this->name.' '.$this->strength.($this->form ? ' ('.$this->form.')' : ''));
    }
}
