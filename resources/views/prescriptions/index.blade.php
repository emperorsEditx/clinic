<x-layouts.app title="Prescriptions">
    <x-page-header title="Prescriptions" subtitle="Finalised, amended and repeat prescriptions.">
        <x-slot:actions>@can('prescriptions.manage')<a href="{{ route('prescription-templates.index') }}" class="btn btn-secondary">Templates</a>@endcan</x-slot:actions>
    </x-page-header>
    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-7" aria-label="Filter prescriptions">
            <div class="lg:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Number, patient or medicine"></div>
            <x-field name="status" label="Status" type="select" :options="App\Enums\PrescriptionStatus::options()" :value="$filters['status'] ?? null" empty="Any (not drafts)" />
            <x-field name="type" label="Type" type="select" :options="['new' => 'New', 'repeat' => 'Repeat', 'amendment' => 'Amendment']" :value="$filters['type'] ?? null" empty="Any" />
            <x-field name="doctor" label="Doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex gap-2 sm:col-span-2 lg:col-span-7"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>@if(array_filter($filters))<a href="{{ route('prescriptions.index') }}" class="btn btn-ghost">Reset</a>@endif</div>
        </form>
        @if ($prescriptions->isEmpty())
            <x-empty icon="document" title="No prescriptions found" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date</th><th>Prescription</th><th>Patient</th><th>Doctor</th><th>Medicines</th><th>Status</th><th class="text-right"><span class="sr-only">Print</span></th></tr></thead>
                <tbody>
                @foreach ($prescriptions as $rx)
                    <tr>
                        <td class="whitespace-nowrap">{{ fmt_date($rx->prescription_date) }}</td>
                        <td><a href="{{ route('prescriptions.show', $rx) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $rx->prescription_number }}</a>
                            @if ($rx->repeat_of_id)<x-badge color="indigo">Repeat</x-badge>@endif @if ($rx->previous_version_id)<x-badge color="amber">v{{ $rx->version }}</x-badge>@endif</td>
                        <td><a href="{{ route('patients.show', $rx->patient) }}" class="font-medium hover:text-brand-700">{{ $rx->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $rx->patient->patient_code }}</div></td>
                        <td class="text-slate-600">{{ $rx->doctor->name }}</td>
                        <td class="tabular-nums">{{ $rx->items_count }}</td>
                        <td><x-badge :status="$rx->status" /></td>
                        <td class="text-right">@unless($rx->isDraft())<a href="{{ route('prescriptions.pdf', $rx) }}" target="_blank" class="btn btn-ghost btn-sm" aria-label="Print {{ $rx->prescription_number }}"><x-icon name="printer" /></a>@endunless</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $prescriptions->links() }}
        @endif
    </div>
</x-layouts.app>
