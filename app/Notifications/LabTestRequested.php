<?php

namespace App\Notifications;

use App\Models\LaboratoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LabTestRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LaboratoryRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** Minimal, non-clinical payload: identifiers only. */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New lab request',
            'message' => $this->request->request_number.' · '.$this->request->test_name.' ('.$this->request->priority->label().')',
            'url' => route('laboratory.requests.show', $this->request, false),
        ];
    }
}
