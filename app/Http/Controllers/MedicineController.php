<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicineRequest;
use App\Models\Medicine;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('medicines.manage'), 403);

        return view('medicines.index', [
            'medicines' => Medicine::query()->search($request->input('q'))
                ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false), fn ($q) => $q->where('is_active', true))
                ->orderBy('name')->paginate(30)->withQueryString(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('prescriptions.manage') || $request->user()->can('medicines.manage'), 403);
        $term = (string) $request->input('q', '');

        $results = strlen(trim($term)) < 2 ? collect() : Medicine::query()->where('is_active', true)->search($term)->orderBy('name')->limit(15)->get();

        return response()->json($results->map(fn (Medicine $m) => [
            'id' => $m->id, 'name' => $m->name, 'generic_name' => $m->generic_name,
            'strength' => $m->strength, 'form' => $m->form, 'route' => $m->default_route, 'label' => $m->label(),
        ]));
    }

    public function store(MedicineRequest $request, AuditLogger $audit): RedirectResponse
    {
        $medicine = new Medicine($request->validated() + ['is_active' => true]);
        $medicine->created_by = $request->user()->id;
        $medicine->save();
        $audit->created($medicine, 'Added medicine '.$medicine->label());

        return back()->with('success', 'Medicine added to catalogue.');
    }

    public function update(MedicineRequest $request, Medicine $medicine, AuditLogger $audit): RedirectResponse
    {
        $original = $medicine->getOriginal();
        $medicine->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->updated($medicine, $original, 'Updated medicine');

        return back()->with('success', 'Medicine updated.');
    }
}
