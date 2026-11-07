<x-layouts.app :title="$req->request_number">
    <x-page-header :title="$req->test_name" :subtitle="$req->request_number.' · requested '.fmt_datetime($req->created_at).' by '.$req->requester->displayName()" :back="route('laboratory.requests.index')">
        <x-slot:meta><div class="mt-2 flex gap-2"><x-badge :status="$req->status" /><x-badge :status="$req->priority" /></div></x-slot:meta>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Request</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Patient</dt><dd>@can('patients.view')<a href="{{ route('patients.show', [$req->patient, 'tab' => 'laboratory']) }}" class="font-medium text-brand-700 hover:underline">{{ $req->patient->full_name }}</a>@else<span class="font-medium">{{ $req->patient->full_name }}</span>@endcan <span class="text-slate-500">{{ $req->patient->patient_code }} · {{ $req->patient->gender?->label() }}, {{ $req->patient->currentAge() ?? '—' }}</span></dd>
                        <dt>Test</dt><dd>{{ $req->test_name }}{{ $req->test_category ? ' · '.$req->test_category : '' }}</dd>
                        <dt>Laboratory</dt><dd>{{ $req->laboratory_name ?? 'In-house' }}</dd>
                        <dt>Clinical notes</dt><dd class="whitespace-pre-line">{{ $req->clinical_notes ?? '—' }}</dd>
                        @if ($req->consultation)<dt>Consultation</dt><dd>@can('clinical.view')<a href="{{ route('consultations.show', $req->consultation) }}" class="text-brand-700 hover:underline">{{ $req->consultation->consultation_number }}</a>@else{{ $req->consultation->consultation_number }}@endcan</dd>@endif
                        @if ($req->sample_collected_at)<dt>Sample collected</dt><dd>{{ fmt_datetime($req->sample_collected_at) }}</dd>@endif
                        @if ($req->completed_at)<dt>Completed</dt><dd>{{ fmt_datetime($req->completed_at) }}</dd>@endif
                        @if ($req->cancellation_reason)<dt>Status note</dt><dd>{{ $req->cancellation_reason }}</dd>@endif
                    </dl>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Reports</h2></div>
                @forelse ($req->reports as $r)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                        <div><a href="{{ route('laboratory.reports.show', $r) }}" class="text-[13px] font-medium hover:text-brand-700">{{ $r->report_number }} · {{ fmt_date($r->report_date) }}</a>
                            <p class="text-xs text-slate-500">{{ $r->original_filename }} · {{ $r->humanSize() }} · uploaded by {{ $r->uploader->name }}{{ $r->reviewer ? ' · reviewed by '.$r->reviewer->name : '' }}</p></div>
                        <div class="flex items-center gap-1"><x-badge :status="$r->status" />
                            @if ($r->isPreviewable())<a href="{{ route('laboratory.reports.download', [$r, 'inline' => 1]) }}" target="_blank" class="btn btn-ghost btn-sm" aria-label="Preview"><x-icon name="eye" /></a>@endif
                            <a href="{{ route('laboratory.reports.download', $r) }}" class="btn btn-ghost btn-sm" aria-label="Download"><x-icon name="download" /></a>
                        </div>
                    </div>
                @empty
                    <x-empty icon="document" title="No report uploaded yet" compact />
                @endforelse
            </section>
        </div>

        <div class="space-y-6">
            @can('updateStatus', $req)
                @if ($req->allowedManualTransitions())
                    <section class="card">
                        <div class="card-header"><h2 class="card-title">Update status</h2></div>
                        <form method="POST" action="{{ route('laboratory.requests.status', $req) }}" class="card-body space-y-3" x-data="{ s: '' }">
                            @csrf
                            @foreach ($req->allowedManualTransitions() as $t)
                                <label class="flex items-center gap-2 text-[13px]"><input type="radio" name="status" value="{{ $t->value }}" x-model="s" class="border-slate-300 text-brand-600"> {{ $t->label() }}</label>
                            @endforeach
                            <div x-show="s === 'cancelled'" x-cloak><label class="label" for="reason">Reason</label><input id="reason" name="reason" class="input" maxlength="255"></div>
                            <button class="btn btn-primary w-full" :disabled="!s">Update</button>
                        </form>
                    </section>
                @endif
            @endcan

            @can('upload', $req)
                @unless ($req->status === App\Enums\LabRequestStatus::Cancelled)
                    <section class="card">
                        <div class="card-header"><h2 class="card-title">Upload report</h2></div>
                        <form method="POST" action="{{ route('laboratory.requests.upload', $req) }}" enctype="multipart/form-data" class="card-body space-y-4">
                            @csrf
                            @include('laboratory._upload-fields', ['fromRequest' => true, 'defaultLab' => $req->laboratory_name])
                            <button class="btn btn-primary w-full"><x-icon name="upload" /> Upload and complete</button>
                        </form>
                    </section>
                @endunless
            @endcan
        </div>
    </div>
</x-layouts.app>
