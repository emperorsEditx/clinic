<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'administrator';

    public const ROLE_DOCTOR = 'doctor';

    public const ROLE_RECEPTIONIST = 'receptionist';

    public const ROLE_LAB = 'lab_technician';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'designation', 'specialization',
        'qualifications', 'registration_number', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeDoctors(Builder $q): Builder
    {
        return $q->role(self::ROLE_DOCTOR);
    }

    public function isDoctor(): bool
    {
        return $this->hasRole(self::ROLE_DOCTOR);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function displayName(): string
    {
        return $this->isDoctor() && ! str_starts_with(strtolower($this->name), 'dr') ? 'Dr. '.$this->name : $this->name;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr(end($parts) ?: '', 0, 1));
    }

    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_DOCTOR => 'Doctor',
            self::ROLE_RECEPTIONIST => 'Receptionist',
            self::ROLE_LAB => 'Lab Technician',
        ];
    }
}
