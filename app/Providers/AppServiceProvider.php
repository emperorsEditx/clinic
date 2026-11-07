<?php

namespace App\Providers;

use App\Models;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'patient' => Models\Patient::class,
            'medical_history' => Models\PatientMedicalHistory::class,
            'condition' => Models\PatientCondition::class,
            'allergy' => Models\PatientAllergy::class,
            'other_medication' => Models\PatientMedication::class,
            'appointment' => Models\Appointment::class,
            'visit' => Models\Visit::class,
            'consultation' => Models\Consultation::class,
            'prescription' => Models\Prescription::class,
            'medication_course' => Models\MedicationCourse::class,
            'medicine' => Models\Medicine::class,
            'lab_test' => Models\LaboratoryTest::class,
            'lab_request' => Models\LaboratoryRequest::class,
            'lab_report' => Models\LaboratoryReport::class,
            'follow_up' => Models\FollowUp::class,
            'setting' => Models\ClinicSetting::class,
            'role' => Role::class,
            'permission' => Permission::class,
        ]);

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('components.pagination');

        $this->applyClinicTimezone();
    }

    private function applyClinicTimezone(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }
        try {
            $tz = (string) app(Settings::class)->get('timezone');
            if ($tz && in_array($tz, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $tz]);
                date_default_timezone_set($tz);
            }
        } catch (Throwable) {
            // Database not ready (fresh install); keep APP_TIMEZONE.
        }
    }
}
