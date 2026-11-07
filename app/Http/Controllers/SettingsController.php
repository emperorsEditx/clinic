<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request, Settings $settings): View
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        return view('settings.edit', ['s' => $settings->all()]);
    }

    public function update(SettingsRequest $request, Settings $settings, AuditLogger $audit): RedirectResponse
    {
        $data = $request->safe()->except(['clinic_logo', 'remove_logo']);
        $before = $settings->all();

        if ($request->boolean('remove_logo') && $before['clinic_logo']) {
            Storage::disk('public')->delete($before['clinic_logo']);
            $data['clinic_logo'] = null;
        }
        if ($request->hasFile('clinic_logo')) {
            if ($before['clinic_logo']) {
                Storage::disk('public')->delete($before['clinic_logo']);
            }
            $data['clinic_logo'] = $request->file('clinic_logo')->store('branding', 'public');
        }

        $settings->set($data, $request->user()->id);

        $diff = [];
        foreach ($data as $k => $v) {
            if ((string) ($before[$k] ?? '') !== (string) $v) {
                $diff[$k] = ['old' => $before[$k] ?? null, 'new' => $v];
            }
        }
        if ($diff) {
            $audit->log('settings_updated', null, 'Updated clinic settings', $diff);
        }

        return back()->with('success', 'Settings saved.');
    }
}
