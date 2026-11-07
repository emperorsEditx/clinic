<?php

namespace App\Http\Controllers;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()->can('clinical.view') || $request->user()->can('appointments.view'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAccess($request);
        $f = $request->validate([
            'status' => ['nullable', Rule::enum(FollowUpStatus::class)],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $status = $f['status'] ?? 'pending';

        $followUps = FollowUp::query()->with(['patient:id,patient_code,full_name,phone', 'doctor:id,name'])
            ->where('status', $status)
            ->when($f['doctor'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('follow_up_date', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('follow_up_date', '<=', $d))
            ->orderBy('follow_up_date')->paginate(config('clinic.per_page'))->withQueryString();

        return view('follow-ups.index', [
            'followUps' => $followUps, 'filters' => $f + ['status' => $status],
            'doctors' => User::query()->doctors()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function status(Request $request, FollowUp $followUp, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeAccess($request);
        abort_unless($request->user()->can('appointments.manage') || $request->user()->can('consultations.manage'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['missed', 'cancelled', 'pending'])]]);
        $old = $followUp->status;
        $followUp->update(['status' => $data['status']]);
        $audit->log('status_changed', $followUp, 'Follow-up status changed', ['status' => ['old' => $old->value, 'new' => $data['status']]]);

        return back()->with('success', 'Follow-up updated.');
    }
}
