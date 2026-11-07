<?php

namespace App\Http\Controllers;

use App\Enums\MedicationCourseStatus;
use App\Models\MedicationCourse;
use App\Services\MedicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MedicationCourse::class);
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(MedicationCourseStatus::class)],
            'ending' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);
        $status = $f['status'] ?? 'active';

        $courses = MedicationCourse::query()->with(['patient:id,patient_code,full_name', 'prescription:id,prescription_number,doctor_id', 'prescription.doctor:id,name'])
            ->where('status', $status)
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('medicine_name', 'like', '%'.$t.'%')->orWhereHas('patient', fn ($p) => $p->search($t))))
            ->when(isset($f['ending']), fn ($q) => $q->whereDate('expected_end_date', '<=', today()->addDays((int) $f['ending'])))
            ->orderBy('expected_end_date')->paginate(config('clinic.per_page'))->withQueryString();

        return view('medications.index', ['courses' => $courses, 'filters' => $f + ['status' => $status]]);
    }

    public function status(Request $request, MedicationCourse $course, MedicationService $medications): RedirectResponse
    {
        $this->authorize('update', $course);
        $data = $request->validate([
            'status' => ['required', Rule::enum(MedicationCourseStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $medications->changeCourseStatus($course, MedicationCourseStatus::from($data['status']), $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Medication status updated.');
    }
}
