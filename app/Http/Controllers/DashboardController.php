<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = Carbon::parse($request->input('from', today()));
        $to = Carbon::parse($request->input('to', $from));

        return view('dashboard', $dashboard->build($request->user(), $from, $to) + ['from' => $from, 'to' => $to]);
    }
}
