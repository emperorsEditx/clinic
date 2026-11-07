<div class="overflow-x-auto"><table class="table">
    <thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Route</th><th>Frequency / timing</th><th>Duration</th><th>Qty</th><th>Instructions</th></tr></thead>
    <tbody>
    @forelse ($items as $i)
        <tr>
            <td class="text-slate-400">{{ $loop->iteration }}</td>
            <td><span class="font-medium">{{ $i->medicine_name }}</span> {{ $i->strength }}@if($i->generic_name)<div class="text-xs text-slate-500">{{ $i->generic_name }}</div>@endif @unless($i->medicine_id)<x-badge color="slate">Custom</x-badge>@endunless</td>
            <td>{{ $i->dosage }}</td>
            <td class="text-slate-600">{{ $i->route ?? '—' }}</td>
            <td>{{ $i->frequency }}@if($i->timing)<div class="text-xs text-slate-500">{{ $i->timing }}</div>@endif</td>
            <td class="whitespace-nowrap tabular-nums">{{ $i->duration_days }} day(s)</td>
            <td class="text-slate-600">{{ $i->quantity ?? '—' }}</td>
            <td class="text-slate-600">{{ $i->instructions ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-slate-500">No medicines.</td></tr>
    @endforelse
    </tbody>
</table></div>
