<section class="card">
    <div class="card-header"><h2 class="card-title">Prescriptions</h2>
        @can('repeat', [App\Models\Prescription::class, $patient])@unless($patient->trashed())<a href="{{ route('prescriptions.repeat', $patient) }}" class="btn btn-secondary btn-sm"><x-icon name="duplicate" /> Repeat prescription</a>@endunless @endcan
    </div>
    @if ($prescriptions->isEmpty())
        <x-empty icon="document" title="No prescriptions yet" />
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($prescriptions as $rx)
                <li class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <a href="{{ route('prescriptions.show', $rx) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $rx->prescription_number }}</a>
                            <span class="ml-1 text-xs text-slate-500">{{ fmt_date($rx->prescription_date) }} · {{ $rx->doctor->name }}</span>
                            @if ($rx->repeat_of_id)<x-badge color="indigo">Repeat</x-badge>@endif
                            @if ($rx->previous_version_id)<x-badge color="amber">v{{ $rx->version }} amendment</x-badge>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :status="$rx->status" />
                            @unless ($rx->isDraft())<a href="{{ route('prescriptions.pdf', $rx) }}" target="_blank" class="btn btn-ghost btn-sm" aria-label="Print {{ $rx->prescription_number }}"><x-icon name="printer" /></a>@endunless
                        </div>
                    </div>
                    <ul class="mt-2 space-y-0.5 text-[13px] text-slate-700">
                        @foreach ($rx->items as $i)
                            <li><span class="font-medium">{{ $i->medicine_name }}</span> {{ $i->strength }} — {{ $i->dosage }}, {{ $i->frequency }}{{ $i->timing ? ', '.strtolower($i->timing) : '' }}, {{ $i->duration_days }} day(s)</li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
        {{ $prescriptions->links() }}
    @endif
</section>
