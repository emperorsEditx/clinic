<section class="card">
    <div class="card-header"><h2 class="card-title">Visit history</h2>@can('visits.manage')@unless($patient->trashed())<a href="{{ route('visits.create', ['patient' => $patient->id]) }}" class="btn btn-secondary btn-sm"><x-icon name="plus" /> New visit</a>@endunless @endcan</div>
    @if ($visits->isEmpty())
        <x-empty icon="clipboard" title="No visits recorded" />
    @else
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Date</th><th>Visit</th><th>Type</th><th>Doctor</th><th>Chief complaint</th>@if($clinical)<th>Diagnosis</th>@endif<th>Status</th></tr></thead>
            <tbody>
            @foreach ($visits as $v)
                <tr>
                    <td class="whitespace-nowrap">{{ fmt_datetime($v->visit_at) }}</td>
                    <td class="whitespace-nowrap"><a href="{{ route('visits.show', $v) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $v->visit_number }}</a></td>
                    <td class="whitespace-nowrap text-slate-600">{{ $v->visitTypeLabel() }}</td>
                    <td class="whitespace-nowrap text-slate-600">{{ $v->doctor->name }}</td>
                    <td class="text-slate-600">{{ $v->chief_complaint ?? '—' }}</td>
                    @if ($clinical)<td class="text-slate-700">{{ \Illuminate\Support\Str::limit($v->consultations->pluck('diagnosis')->filter()->implode('; '), 80) ?: '—' }}</td>@endif
                    <td><x-badge :status="$v->status" /></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        {{ $visits->links() }}
    @endif
</section>
