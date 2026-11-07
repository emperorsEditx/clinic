<?php

use App\Support\Settings;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('settings')) {
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(mixed $value, string $fallback = '—'): string
    {
        if (blank($value)) {
            return $fallback;
        }
        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $date->format((string) settings('date_format', 'd M Y'));
    }
}

if (! function_exists('fmt_time')) {
    function fmt_time(mixed $value, string $fallback = '—'): string
    {
        if (blank($value)) {
            return $fallback;
        }
        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return $date->format((string) settings('time_format', 'h:i A'));
    }
}

if (! function_exists('fmt_datetime')) {
    function fmt_datetime(mixed $value, string $fallback = '—'): string
    {
        if (blank($value)) {
            return $fallback;
        }

        return fmt_date($value).' '.fmt_time($value);
    }
}
