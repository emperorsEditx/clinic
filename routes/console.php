<?php

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Services\MedicationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('clinic:daily-maintenance', function (MedicationService $medications) {
    $completed = $medications->completeExpiredCourses();
    $missed = FollowUp::query()->where('status', FollowUpStatus::Pending->value)
        ->whereDate('follow_up_date', '<', now()->subDays(30)->toDateString())
        ->update(['status' => FollowUpStatus::Missed->value, 'updated_at' => now()]);
    $this->info("Medication courses completed: $completed; follow-ups marked missed: $missed");
})->purpose('Complete elapsed medication courses and close stale follow-ups');

Schedule::command('clinic:daily-maintenance')->dailyAt('00:15')->withoutOverlapping();
Schedule::command('backup:clean')->daily()->at('01:00')->when(fn () => config('clinic.backup_enabled', false));
Schedule::command('backup:run')->daily()->at('01:30')->when(fn () => config('clinic.backup_enabled', false));
