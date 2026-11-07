<section class="card">
    <div class="card-header"><h2 class="card-title">Consultations</h2></div>
    @if ($consultations->isEmpty())
        <x-empty icon="stethoscope" title="No consultations yet">Consultations are started from a visit.</x-empty>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($consultations as $c)
                <li class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <a href="{{ route('consultations.show', $c) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ fmt_datetime($c->completed_at ?? $c->created_at) }}</a>
                            <p class="text-xs text-slate-500">{{ $c->consultation_number }} · {{ $c->doctor->name }}@if($c->amendment_count) · amended {{ $c->amendment_count }}×@endif</p>
                        </div>
                        <x-badge :status="$c->status" />
                    </div>
                    <dl class="dl mt-3">
                        @if ($c->symptoms)<dt>Symptoms</dt><dd class="prose-clinical">{{ \Illuminate\Support\Str::limit($c->symptoms, 300) }}</dd>@endif
                        <dt>Diagnosis</dt><dd class="prose-clinical">{{ $c->diagnosis ?: '—' }}</dd>
                        @if ($c->treatment_plan)<dt>Treatment plan</dt><dd class="prose-clinical">{{ \Illuminate\Support\Str::limit($c->treatment_plan, 300) }}</dd>@endif
                        @if ($c->vitals && ! $c->vitals->isEmpty())<dt>Vitals</dt><dd>@include('consultations._vitals-inline', ['v' => $c->vitals])</dd>@endif
                        @if ($c->follow_up_date)<dt>Follow-up</dt><dd>{{ fmt_date($c->follow_up_date) }}</dd>@endif
                    </dl>
                </li>
            @endforeach
        </ul>
        {{ $consultations->links() }}
    @endif
</section>
