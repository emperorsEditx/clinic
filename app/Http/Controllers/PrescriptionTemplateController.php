<?php

namespace App\Http\Controllers;

use App\Models\PrescriptionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionTemplateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('prescriptions.manage'), 403);

        return view('prescriptions.templates', [
            'templates' => PrescriptionTemplate::query()->visibleTo($request->user())->with('doctor:id,name')->orderBy('name')->get(),
        ]);
    }

    public function destroy(Request $request, PrescriptionTemplate $template): RedirectResponse
    {
        abort_unless($template->doctor_id === $request->user()->id, 403);
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }
}
