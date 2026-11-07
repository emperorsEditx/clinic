<x-layouts.app title="Consultations">
    <x-page-header title="Consultations" subtitle="Consultations are started from a visit in the waiting room." />
    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6" aria-label="Filter consultations">
            <div class="lg:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Number, diagnosis or patient"></div>
            <x-field name="status" label="Status" type="select" :options="App\Enums\ConsultationStatus::options()" :value="$filters['status'] ?? null" empty="Any" />
            <x-field name="doctor" label="Doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-6">
                <button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>
                @if(array_filter($filters))<a href="{{ route('consultations.index') }}" class="btn btn-ghost">Reset</a>@endif
                @if (auth()->user()->isDoctor())<label class="ml-auto flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="mine" value="1" @checked(request()->boolean('mine')) onchange="this.form.submit()" class="rounded border-slate-300"> Only mine</label>@endif
            </div>
        </form>
        @if ($consultations->isEmpty())
            <x-empty icon="stethoscope" title="No consultations found" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date</th><th>Consultation</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($consultations as $c)
                    <tr class="row-link" onclick="if (!event.target.closest('a')) window.location='{{ route('consultations.show', $c) }}'">
                        <td class="whitespace-nowrap">{{ fmt_datetime($c->completed_at ?? $c->created_at) }}</td>
                        <td><a href="{{ route('consultations.show', $c) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $c->consultation_number }}</a></td>
                        <td><a href="{{ route('patients.show', $c->patient) }}" class="font-medium hover:text-brand-700">{{ $c->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $c->patient->patient_code }}</div></td>
                        <td class="text-slate-600">{{ $c->doctor->name }}</td>
                        <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($c->diagnosis, 80) ?: '—' }}</td>
                        <td><x-badge :status="$c->status" />@if($c->amendment_count) <x-badge color="amber">Amended</x-badge>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $consultations->links() }}
        @endif
    </div>
</x-layouts.app>
