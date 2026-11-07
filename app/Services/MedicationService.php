<?php

namespace App\Services;

use App\Enums\MedicationCourseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\MedicationCourse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MedicationService
{
    public function __construct(private AuditLogger $audit) {}

    public function changeCourseStatus(MedicationCourse $course, MedicationCourseStatus $to, User $by, ?string $reason): MedicationCourse
    {
        if ($course->status === $to) {
            return $course;
        }
        if ($course->status === MedicationCourseStatus::Completed || $course->status === MedicationCourseStatus::Discontinued) {
            throw new BusinessRuleException('Completed or discontinued courses cannot be reopened. Prescribe a repeat instead.');
        }
        if (in_array($to, [MedicationCourseStatus::Discontinued, MedicationCourseStatus::OnHold], true) && blank($reason)) {
            throw new BusinessRuleException('A reason is required to discontinue or hold a medication.');
        }

        return DB::transaction(function () use ($course, $to, $by, $reason) {
            $from = $course->status;
            $course->forceFill([
                'status' => $to,
                'status_reason' => $reason,
                'status_changed_at' => now(),
                'status_changed_by' => $by->id,
            ])->save();
            $this->audit->log('status_changed', $course, "Medication {$from->label()} → {$to->label()}", [
                'status' => ['old' => $from->value, 'new' => $to->value], 'reason' => $reason,
            ]);

            return $course;
        });
    }

    /** Daily job: active courses past their expected end date are marked completed. */
    public function completeExpiredCourses(): int
    {
        return MedicationCourse::query()
            ->where('status', MedicationCourseStatus::Active->value)
            ->whereDate('expected_end_date', '<', now()->toDateString())
            ->update([
                'status' => MedicationCourseStatus::Completed->value,
                'status_reason' => 'Course duration elapsed',
                'status_changed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
