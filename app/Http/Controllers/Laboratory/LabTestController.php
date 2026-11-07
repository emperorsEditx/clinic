<?php

namespace App\Http\Controllers\Laboratory;

use App\Http\Controllers\Controller;
use App\Http\Requests\LabTestRequest;
use App\Models\LaboratoryTest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabTestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('lab_tests.manage') || $request->user()->can('lab.request'), 403);

        return view('laboratory.tests.index', [
            'tests' => LaboratoryTest::query()
                ->when($request->input('q'), fn ($q, $t) => $q->where('name', 'like', '%'.$t.'%')->orWhere('category', 'like', '%'.$t.'%'))
                ->orderBy('category')->orderBy('name')->paginate(50)->withQueryString(),
            'categories' => LaboratoryTest::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function store(LabTestRequest $request, AuditLogger $audit): RedirectResponse
    {
        $test = LaboratoryTest::query()->create($request->validated() + ['is_active' => true]);
        $audit->created($test, 'Added lab test '.$test->name);

        return back()->with('success', 'Test added to catalogue.');
    }

    public function update(LabTestRequest $request, LaboratoryTest $laboratoryTest, AuditLogger $audit): RedirectResponse
    {
        $original = $laboratoryTest->getOriginal();
        $laboratoryTest->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        $audit->updated($laboratoryTest, $original, 'Updated lab test');

        return back()->with('success', 'Test updated.');
    }
}
