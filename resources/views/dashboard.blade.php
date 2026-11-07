<x-layouts.app title="Dashboard">
    <x-page-header :title="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening')).', '.auth()->user()->name" :subtitle="$from->isSameDay($to) ? fmt_date($from).' · '.$from->format('l') : fmt_date($from).' – '.fmt_date($to)">
        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-end gap-2" aria-label="Date filter">
                <div><label class="sr-only" for="d_from">From</label><input id="d_from" type="date" name="from" value="{{ $from->toDateString() }}" class="input h-9 w-auto"></div>
                <span class="pb-2 text-slate-400">–</span>
                <div><label class="sr-only" for="d_to">To</label><input id="d_to" type="date" name="to" value="{{ $to->toDateString() }}" class="input h-9 w-auto"></div>
                <button class="btn btn-secondary h-9">Apply</button>
                @if (! $from->isToday() || ! $to->isToday())<a href="{{ route('dashboard') }}" class="btn btn-ghost h-9">Today</a>@endif
            </form>
            @can('patients.create')<a href="{{ route('patients.create') }}" class="btn btn-primary h-9"><x-icon name="user-plus" /> Register patient</a>@endcan
        </x-slot:actions>
    </x-page-header>

    @if ($cards)
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            @foreach ($cards as $card)
                <x-stat :label="$card['label']" :value="$card['value']" :icon="$card['icon']" :url="$card['url']" :hint="$card['hint'] ?? null" />
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @isset($sections['appointments'])
                <section class="card" aria-labelledby="h-appts">
                    <div class="card-header"><h2 id="h-appts" class="card-title">Appointments {{ $from->isSameDay($to) && $from->isToday() ? 'today' : 'in period' }}</h2><a href="{{ route('appointments.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="text-xs font-medium text-brand-700 hover:underline">View all</a></div>
                    @if ($sections['appointments']->isEmpty())
                        <x-empty icon="calendar" title="No appointments" compact>Nothing booked for this period.</x-empty>
                    @else
                        <div class="overflow-x-auto"><table class="table"><thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Status</th><th class="text-right no-print"></th></tr></thead><tbody>
                        @foreach ($sections['appointments'] as $a)
                            <tr>
                                <td class="whitespace-nowrap tabular-nums">@unless($from->isSameDay($to)){{ fmt_date($a->appointment_date) }}<br>@endunless<span class="font-medium">{{ fmt_time($a->scheduledAt()) }}</span></td>
                                <td><a href="{{ route('patients.show', $a->patient) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $a->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $a->patient->patient_code }}</div></td>
                                <td class="text-slate-600">{{ $a->doctor->name }}</td>
                                <td class="text-slate-600">{{ $a->appointment_type }}</td>
                                <td><x-badge :status="$a->status" /></td>
                                <td class="text-right">
                                    @if ($a->isOpen() && auth()->user()->can('checkIn', $a))
                                        <form method="POST" action="{{ route('appointments.check-in', $a) }}">@csrf<button class="btn btn-secondary btn-sm">Check in</button></form>
                                    @else
                                        <a href="{{ route('appointments.show', $a) }}" class="btn btn-ghost btn-sm">Open</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody></table></div>
                    @endif
                </section>
            @endisset

            @isset($sections['waiting'])
                <section class="card" aria-labelledby="h-wait">
                    <div class="card-header"><h2 id="h-wait" class="card-title">Waiting room</h2><span class="text-xs text-slate-500">{{ $sections['waiting']->count() }} open visit(s)</span></div>
                    @if ($sections['waiting']->isEmpty())
                        <x-empty icon="clipboard" title="No patients waiting" compact />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($sections['waiting'] as $v)
                                <li class="flex items-center justify-between gap-3 px-5 py-3">
                                    <div class="min-w-0"><a href="{{ route('visits.show', $v) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $v->patient->full_name }}</a>
                                        <p class="truncate text-xs text-slate-500">{{ $v->patient->patient_code }} · {{ $v->doctor->name }} · arrived {{ $v->visit_at->diffForHumans() }}@if($v->chief_complaint) · {{ $v->chief_complaint }}@endif</p></div>
                                    <div class="flex items-center gap-2"><x-badge :status="$v->status" />
                                        @if (auth()->user()->can('consult', $v) && $v->doctor_id === auth()->id())
                                            <form method="POST" action="{{ route('visits.consult', $v) }}">@csrf<button class="btn btn-primary btn-sm">{{ $v->status === \App\Enums\VisitStatus::InConsultation ? 'Continue' : 'Start' }} consultation</button></form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endisset

            @isset($sections['recent_consultations'])
                <section class="card" aria-labelledby="h-cons">
                    <div class="card-header"><h2 id="h-cons" class="card-title">Recent consultations</h2><a href="{{ route('consultations.index') }}" class="text-xs font-medium text-brand-700 hover:underline">View all</a></div>
                    @if ($sections['recent_consultations']->isEmpty())
                        <x-empty icon="stethoscope" title="No consultations yet" compact />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($sections['recent_consultations'] as $c)
                                <li class="flex items-center justify-between gap-3 px-5 py-3">
                                    <div class="min-w-0"><a href="{{ route('consultations.show', $c) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $c->patient->full_name }}</a>
                                        <p class="truncate text-xs text-slate-500">{{ $c->consultation_number }} · {{ $c->doctor->name }} · {{ $c->diagnosis ? \Illuminate\Support\Str::limit($c->diagnosis, 60) : 'No diagnosis yet' }}</p></div>
                                    <x-badge :status="$c->status" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endisset

            @isset($sections['recent_visits'])
                <section class="card" aria-labelledby="h-visits">
                    <div class="card-header"><h2 id="h-visits" class="card-title">Recent visits</h2><a href="{{ route('visits.index') }}" class="text-xs font-medium text-brand-700 hover:underline">View all</a></div>
                    @if ($sections['recent_visits']->isEmpty())
                        <x-empty icon="clipboard" title="No visits recorded" compact />
                    @else
                        <div class="overflow-x-auto"><table class="table"><tbody>
                            @foreach ($sections['recent_visits'] as $v)
                                <tr><td class="whitespace-nowrap text-slate-500">{{ fmt_datetime($v->visit_at) }}</td><td><a class="font-medium hover:text-brand-700" href="{{ route('visits.show', $v) }}">{{ $v->patient->full_name }}</a> <span class="text-xs text-slate-500">{{ $v->patient->patient_code }}</span></td><td class="text-slate-600">{{ $v->doctor->name }}</td><td><x-badge :status="$v->status" /></td></tr>
                            @endforeach
                        </tbody></table></div>
                    @endif
                </section>
            @endisset
        </div>

        <div class="space-y-6">
            @isset($sections['follow_ups'])
                <section class="card" aria-labelledby="h-fu">
                    <div class="card-header"><h2 id="h-fu" class="card-title">Upcoming follow-ups</h2><a href="{{ route('follow-ups.index') }}" class="text-xs font-medium text-brand-700 hover:underline">View all</a></div>
                    @if ($sections['follow_ups']->isEmpty())
                        <x-empty icon="arrow-path" title="No follow-ups due" compact />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($sections['follow_ups'] as $f)
                                <li class="px-5 py-3">
                                    <div class="flex items-center justify-between gap-2"><a href="{{ route('patients.show', $f->patient) }}" class="truncate font-medium text-slate-900 hover:text-brand-700">{{ $f->patient->full_name }}</a>
                                        <span @class(['text-xs font-medium tabular-nums', 'text-red-600' => $f->follow_up_date->isPast() && ! $f->follow_up_date->isToday(), 'text-amber-700' => $f->follow_up_date->isToday(), 'text-slate-500' => $f->follow_up_date->isFuture()])>{{ $f->follow_up_date->isToday() ? 'Today' : fmt_date($f->follow_up_date) }}</span></div>
                                    <p class="truncate text-xs text-slate-500">{{ $f->patient->phone ?? $f->patient->patient_code }} · {{ $f->doctor?->name }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endisset

            @isset($sections['recent_patients'])
                <section class="card" aria-labelledby="h-pat">
                    <div class="card-header"><h2 id="h-pat" class="card-title">Recent registrations</h2><a href="{{ route('patients.index') }}" class="text-xs font-medium text-brand-700 hover:underline">View all</a></div>
                    @if ($sections['recent_patients']->isEmpty())
                        <x-empty icon="users" title="No patients registered" compact />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($sections['recent_patients'] as $p)
                                <li class="flex items-center justify-between px-5 py-2.5"><div class="min-w-0"><a href="{{ route('patients.show', $p) }}" class="truncate font-medium text-slate-900 hover:text-brand-700">{{ $p->full_name }}</a><p class="text-xs text-slate-500">{{ $p->patient_code }} · {{ $p->gender?->label() }}</p></div><span class="text-xs text-slate-400">{{ $p->created_at?->diffForHumans(short: true) }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endisset

            @isset($sections['recent_reports'])
                <section class="card" aria-labelledby="h-lab">
                    <div class="card-header"><h2 id="h-lab" class="card-title">Recently uploaded lab reports</h2><a href="{{ route('laboratory.reports.index') }}" class="text-xs font-medium text-brand-700 hover:underline">View all</a></div>
                    @if ($sections['recent_reports']->isEmpty())
                        <x-empty icon="beaker" title="No reports uploaded" compact />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($sections['recent_reports'] as $r)
                                <li class="flex items-center justify-between gap-2 px-5 py-2.5"><div class="min-w-0"><a href="{{ route('laboratory.reports.show', $r) }}" class="truncate font-medium text-slate-900 hover:text-brand-700">{{ $r->test_name }}</a><p class="truncate text-xs text-slate-500">{{ $r->patient->full_name }} · {{ fmt_date($r->report_date) }}</p></div><x-badge :status="$r->status" /></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endisset
        </div>
    </div>
</x-layouts.app>
