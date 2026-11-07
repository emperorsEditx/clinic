<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistoryEntryRequest;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Services\MedicalHistoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalHistoryController extends Controller
{
    public function __construct(private MedicalHistoryService $history) {}

    public function store(HistoryEntryRequest $request, Patient $patient, string $type): RedirectResponse
    {
        $this->authorize('manageHistory', $patient);
        $this->history->add($patient, $type, $request->validated(), $request->user());

        return $this->back($patient, $type, 'Entry added to medical history.');
    }

    public function update(HistoryEntryRequest $request, Patient $patient, string $type, int $entry): RedirectResponse
    {
        $this->authorize('manageHistory', $patient);
        $this->history->update($this->find($patient, $type, $entry), $request->validated(), $request->user());

        return $this->back($patient, $type, 'Medical history entry updated. The previous values are kept in the change log.');
    }

    public function destroy(Request $request, Patient $patient, string $type, int $entry): RedirectResponse
    {
        $this->authorize('manageHistory', $patient);
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']]);
        $this->history->remove($this->find($patient, $type, $entry), $data['reason']);

        return $this->back($patient, $type, 'Entry removed. It remains in the change log.');
    }

    /** Change log of a history entry — shows how an entry evolved. */
    public function changes(Patient $patient, string $type, int $entry): View
    {
        $this->authorize('viewClinical', $patient);
        $model = $this->find($patient, $type, $entry, true);
        $logs = ActivityLog::query()->with('user:id,name')
            ->where('entity_type', $model->getMorphClass())->where('entity_id', $model->getKey())
            ->latest('id')->get();

        return view('patients.history-changes', compact('patient', 'model', 'logs', 'type'));
    }

    private function find(Patient $patient, string $type, int $id, bool $withTrashed = false): Model
    {
        $class = MedicalHistoryService::modelFor($type);

        return $class::query()->when($withTrashed, fn ($q) => $q->withTrashed())
            ->where('patient_id', $patient->id)->findOrFail($id);
    }

    private function back(Patient $patient, string $type, string $message): RedirectResponse
    {
        $tab = $type === 'medication' ? 'medications' : 'history';

        return redirect()->route('patients.show', [$patient, 'tab' => $tab])->with('success', $message);
    }
}
