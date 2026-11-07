<x-layouts.app title="Audit log">
    <x-page-header title="Audit log" subtitle="Append-only record of clinical, administrative and access events. Passwords and tokens are never logged." />
    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6" aria-label="Filter audit log">
            <x-field name="user" label="User" type="select" :options="$users->pluck('name', 'id')" :value="$filters['user'] ?? null" empty="Anyone" />
            <x-field name="action" label="Action" type="select" :options="$actions->mapWithKeys(fn ($a) => [$a => ucfirst(str_replace('_', ' ', $a))])" :value="$filters['action'] ?? null" empty="Any" />
            <x-field name="entity" label="Record type" type="select" :options="$entities->mapWithKeys(fn ($e) => [$e => ucfirst(str_replace('_', ' ', $e))])" :value="$filters['entity'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex items-end gap-2"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>@if(array_filter($filters))<a href="{{ route('audit.index') }}" class="btn btn-ghost">Reset</a>@endif</div>
            @if (! empty($filters['patient']))<input type="hidden" name="patient" value="{{ $filters['patient'] }}">@endif
        </form>
        @if ($logs->isEmpty())
            <x-empty icon="shield" title="No matching events" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Patient</th><th>Details</th></tr></thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr x-data="{ open: false }">
                        <td class="whitespace-nowrap text-xs">{{ fmt_datetime($log->created_at) }}<div class="text-slate-400">{{ $log->ip_address }}</div></td>
                        <td class="whitespace-nowrap">{{ $log->user?->name ?? 'System' }}</td>
                        <td><x-badge :color="match (true) { str_contains($log->action, 'fail') || str_contains($log->action, 'delet') || str_contains($log->action, 'discard') => 'red', $log->action === 'created' => 'green', str_contains($log->action, 'amend') => 'amber', default => 'blue' }">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</x-badge></td>
                        <td class="whitespace-nowrap text-xs text-slate-600">{{ $log->entity_type ? ucfirst(str_replace('_', ' ', $log->entity_type)).' #'.$log->entity_id : '—' }}</td>
                        <td>@if($log->patient)<a href="{{ route('audit.index', ['patient' => $log->patient_id]) }}" class="text-xs hover:text-brand-700" title="Show all events for this patient">{{ $log->patient->full_name }}<span class="block font-mono text-slate-500">{{ $log->patient->patient_code }}</span></a>@else — @endif</td>
                        <td class="min-w-[16rem] text-xs text-slate-700">{{ $log->description }}
                            @if ($log->changes)
                                <button type="button" class="ml-1 text-brand-700 hover:underline" x-on:click="open = !open" x-text="open ? 'Hide changes' : 'Show changes'"></button>
                                <div x-show="open" x-cloak class="mt-2 rounded-lg bg-slate-50 p-2"><x-changes :changes="$log->changes" /></div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $logs->links() }}
        @endif
    </div>
</x-layouts.app>
