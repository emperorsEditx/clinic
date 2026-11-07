{{-- Renders an audit "changes" payload: {field: {old, new}} diffs, {attributes: {...}} snapshots, or plain key/values. --}}
@props(['changes' => null])
@php
    $fmt = function ($v) {
        if ($v === null || $v === '') return '—';
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return (string) $v;
    };
    $label = fn ($k) => ucfirst(str_replace('_', ' ', (string) $k));
    $changes = is_array($changes) ? $changes : [];
    $snapshot = $changes['attributes'] ?? null;
@endphp
@if ($changes)
    <dl {{ $attributes->merge(['class' => 'space-y-1 text-xs']) }}>
        @if (is_array($snapshot))
            @foreach (\Illuminate\Support\Arr::except($changes, 'attributes') as $k => $v)
                <div class="flex gap-2"><dt class="w-36 shrink-0 font-medium text-slate-600">{{ $label($k) }}</dt><dd class="min-w-0 break-words font-medium text-slate-900">{{ \Illuminate\Support\Str::limit($fmt($v), 300) }}</dd></div>
            @endforeach
            @foreach ($snapshot as $k => $v)
                @continue(in_array($k, ['patient_id', 'recorded_by', 'updated_by', 'created_by', 'deleted_at'], true) || $v === null || $v === '')
                <div class="flex gap-2"><dt class="w-36 shrink-0 text-slate-500">{{ $label($k) }}</dt><dd class="min-w-0 break-words text-slate-800">{{ \Illuminate\Support\Str::limit($fmt($v), 300) }}</dd></div>
            @endforeach
        @else
            @foreach ($changes as $k => $v)
                <div class="flex gap-2">
                    <dt class="w-36 shrink-0 text-slate-500">{{ $label($k) }}</dt>
                    <dd class="min-w-0 break-words">
                        @if (is_array($v) && array_key_exists('old', $v) && array_key_exists('new', $v))
                            <span class="text-red-700 line-through decoration-red-300">{{ \Illuminate\Support\Str::limit($fmt($v['old']), 300) }}</span>
                            <span class="mx-1 text-slate-400">→</span>
                            <span class="text-emerald-800">{{ \Illuminate\Support\Str::limit($fmt($v['new']), 300) }}</span>
                        @else
                            <span class="text-slate-800">{{ \Illuminate\Support\Str::limit($fmt($v), 300) }}</span>
                        @endif
                    </dd>
                </div>
            @endforeach
        @endif
    </dl>
@endif
