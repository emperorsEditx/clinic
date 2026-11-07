<x-layouts.app :title="$report->report_number">
    <x-page-header :title="$report->test_name.' report'" :subtitle="$report->report_number.' · '.fmt_date($report->report_date)" :back="route('laboratory.reports.index')">
        <x-slot:meta><div class="mt-2"><x-badge :status="$report->status" /></div></x-slot:meta>
        <x-slot:actions>
            @if ($report->isPreviewable())<a href="{{ route('laboratory.reports.download', [$report, 'inline' => 1]) }}" target="_blank" class="btn btn-secondary"><x-icon name="eye" /> Open</a>@endif
            <a href="{{ route('laboratory.reports.download', $report) }}" class="btn btn-secondary"><x-icon name="download" /> Download</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if ($report->isPreviewable())
                <section class="card overflow-hidden">
                    <div class="card-header"><h2 class="card-title">Preview</h2><span class="text-xs text-slate-500">{{ $report->original_filename }} · {{ $report->humanSize() }}</span></div>
                    @if ($report->isImage())
                        <div class="bg-slate-50 p-4"><img src="{{ route('laboratory.reports.download', [$report, 'inline' => 1]) }}" alt="Laboratory report {{ $report->report_number }}" class="mx-auto max-h-[80vh] rounded border border-slate-200 bg-white"></div>
                    @else
                        <iframe src="{{ route('laboratory.reports.download', [$report, 'inline' => 1]) }}" title="Laboratory report {{ $report->report_number }}" class="h-[75vh] w-full border-0 bg-slate-50"></iframe>
                    @endif
                </section>
            @endif
            @if ($report->result_summary)
                <section class="card"><div class="card-header"><h2 class="card-title">Result summary</h2></div><div class="card-body prose-clinical">{{ $report->result_summary }}</div></section>
            @endif
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Details</h2></div>
                <div class="card-body">
                    <dl class="dl sm:grid-cols-[7rem_1fr]">
                        <dt>Patient</dt><dd>@can('patients.view')<a href="{{ route('patients.show', [$report->patient, 'tab' => 'laboratory']) }}" class="text-brand-700 hover:underline">{{ $report->patient->full_name }}</a>@else{{ $report->patient->full_name }}@endcan<br><span class="text-xs text-slate-500">{{ $report->patient->patient_code }}</span></dd>
                        <dt>Test</dt><dd>{{ $report->test_name }}{{ $report->test_category ? ' · '.$report->test_category : '' }}</dd>
                        <dt>Laboratory</dt><dd>{{ $report->laboratory_name ?? '—' }}</dd>
                        <dt>Test date</dt><dd>{{ fmt_date($report->test_date) }}</dd>
                        <dt>Report date</dt><dd>{{ fmt_date($report->report_date) }}</dd>
                        @if ($report->request)<dt>Request</dt><dd><a href="{{ route('laboratory.requests.show', $report->request) }}" class="text-brand-700 hover:underline">{{ $report->request->request_number }}</a><br><span class="text-xs text-slate-500">by {{ $report->request->requester->name }}</span></dd>@endif
                        @if ($report->visit)<dt>Visit</dt><dd>@can('visits.view')<a href="{{ route('visits.show', $report->visit) }}" class="text-brand-700 hover:underline">{{ $report->visit->visit_number }}</a>@else{{ $report->visit->visit_number }}@endcan</dd>@endif
                        <dt>Uploaded</dt><dd>{{ $report->uploader->name }}<br><span class="text-xs text-slate-500">{{ fmt_datetime($report->created_at) }}</span></dd>
                        <dt>File</dt><dd class="break-all text-xs">{{ $report->original_filename }} ({{ $report->humanSize() }})</dd>
                    </dl>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Doctor review</h2></div>
                <div class="card-body">
                    @if ($report->reviewer)
                        <p class="text-[13px]"><span class="font-medium">{{ $report->reviewer->displayName() }}</span> <span class="text-xs text-slate-500">{{ fmt_datetime($report->reviewed_at) }}</span></p>
                        <p class="prose-clinical mt-2">{{ $report->review_notes ?: 'No notes.' }}</p>
                    @else
                        @can('review', $report)
                            <form method="POST" action="{{ route('laboratory.reports.review', $report) }}" class="space-y-3">
                                @csrf
                                <x-field name="review_notes" label="Doctor's notes" type="textarea" rows="4" />
                                <button class="btn btn-primary w-full"><x-icon name="check" /> Mark as reviewed</button>
                            </form>
                        @else
                            <p class="text-[13px] text-slate-500">Awaiting review by a doctor.</p>
                        @endcan
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
