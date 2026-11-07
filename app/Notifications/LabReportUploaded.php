<?php

namespace App\Notifications;

use App\Models\LaboratoryReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LabReportUploaded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LaboratoryReport $report) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Lab report ready for review',
            'message' => $this->report->report_number.' · '.$this->report->test_name,
            'url' => route('laboratory.reports.show', $this->report, false),
        ];
    }
}
