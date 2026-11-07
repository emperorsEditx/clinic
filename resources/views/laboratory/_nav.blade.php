<nav class="mb-6 flex gap-6 overflow-x-auto border-b border-slate-200" aria-label="Laboratory sections">
    <a href="{{ route('laboratory.requests.index') }}" @class(['tab', 'tab-active' => request()->routeIs('laboratory.requests.*'), 'tab-inactive' => ! request()->routeIs('laboratory.requests.*')])>Requests</a>
    <a href="{{ route('laboratory.reports.index') }}" @class(['tab', 'tab-active' => request()->routeIs('laboratory.reports.*'), 'tab-inactive' => ! request()->routeIs('laboratory.reports.*')])>Reports</a>
    @if (auth()->user()->can('lab_tests.manage') || auth()->user()->can('lab.request'))
        <a href="{{ route('laboratory.tests.index') }}" @class(['tab', 'tab-active' => request()->routeIs('laboratory.tests.*'), 'tab-inactive' => ! request()->routeIs('laboratory.tests.*')])>Test catalogue</a>
    @endif
</nav>
