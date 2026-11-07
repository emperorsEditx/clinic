<section class="card">
    <div class="card-header"><h2 class="card-title">Medical timeline</h2><span class="text-xs text-slate-500">{{ $timeline->total() }} event(s)</span></div>
    @if ($timeline->isEmpty())
        <x-empty icon="clock" title="No events" />
    @else
        <ol class="relative px-5 py-4">
            @foreach ($timeline as $e)
                <li class="relative flex gap-4 pb-5 last:pb-0">
                    @unless ($loop->last)<span class="absolute left-[7px] top-4 h-full w-px bg-slate-200" aria-hidden="true"></span>@endunless
                    <span class="badge-{{ $e['color'] }} relative mt-1 size-[15px] shrink-0 rounded-full ring-4 ring-white"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><x-badge :color="$e['color']">{{ $e['type'] }}</x-badge><span class="text-xs text-slate-500">{{ fmt_datetime($e['at']) }}</span></div>
                        <p class="mt-1 text-[13px] font-medium text-slate-900">@if($e['url'])<a href="{{ $e['url'] }}" class="hover:text-brand-700">{{ $e['title'] }}</a>@else{{ $e['title'] }}@endif</p>
                        @if ($e['detail'])<p class="text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($e['detail'], 200) }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ol>
        {{ $timeline->links() }}
    @endif
</section>
