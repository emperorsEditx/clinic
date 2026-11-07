<?php

namespace App\Support;

use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Clinic settings backed by the clinic_settings table with config defaults.
 */
class Settings
{
    private const CACHE_KEY = 'clinic_settings.all';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $defaults = config('clinic.settings_defaults', []);

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('clinic_settings')) {
                    return [];
                }

                return ClinicSetting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        return $this->loaded = array_merge($defaults, array_filter($stored, fn ($v) => $v !== null && $v !== ''));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @param array<string, mixed> $values */
    public function set(array $values, ?int $userId = null): void
    {
        foreach ($values as $key => $value) {
            ClinicSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $userId]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /** @return list<string> */
    public function appointmentTypes(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->get('appointment_types')))));
    }

    public function logoPath(): ?string
    {
        $logo = $this->get('clinic_logo');

        return $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : null;
    }

    public function logoUrl(): ?string
    {
        $logo = $this->get('clinic_logo');

        return $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->url($logo) : null;
    }

    public function logoDataUri(): ?string
    {
        $path = $this->logoPath();
        if (! $path) {
            return null;
        }
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
