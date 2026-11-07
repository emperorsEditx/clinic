<x-layouts.app title="Medications">
    <x-page-header title="Medication continuation" subtitle="Clinic-prescribed medication courses across all patients, ordered by expected end date." />
    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Filter medications">
            <div class="lg:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Medicine or patient"></div>
            <x-field name="status" label="Status" type="select" :options="App\Enums\MedicationCourseStatus::options()" :value="$filters['status']" :empty="false" />
            <x-field name="ending" label="Ending within" type="select" :options="['0' => 'Overdue / today', '3' => '3 days', '7' => '7 days', '14' => '14 days', '30' => '30 days']" :value="$filters['ending'] ?? null" empty="Any time" />
            <div class="flex items-end gap-2"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button></div>
        </form>
        @if ($courses->isEmpty())
            <x-empty icon="capsule" title="No medication courses match" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Patient</th><th>Medicine</th><th>Start</th><th>Days</th><th>Expected end</th><th>Prescription</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($courses as $course)
                    @php $left = $course->daysRemaining(); @endphp
                    <tr>
                        <td><a href="{{ route('patients.show', [$course->patient, 'tab' => 'medications']) }}" class="font-medium hover:text-brand-700">{{ $course->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $course->patient->patient_code }}</div></td>
                        <td class="font-medium">{{ $course->medicine_name }}@if($course->continuation_instructions)<div class="text-xs font-normal text-slate-500">{{ $course->continuation_instructions }}</div>@endif</td>
                        <td class="whitespace-nowrap">{{ fmt_date($course->start_date) }}</td>
                        <td class="tabular-nums">{{ $course->duration_days }}</td>
                        <td class="whitespace-nowrap">{{ fmt_date($course->expected_end_date) }}
                            @if ($course->status === App\Enums\MedicationCourseStatus::Active && $left !== null)<div class="text-xs {{ $left <= 2 ? 'font-medium text-amber-700' : 'text-slate-500' }}">{{ $left > 0 ? $left.' day(s) left' : ($left === 0 ? 'Ends today' : abs($left).' day(s) overdue') }}</div>@endif</td>
                        <td><a href="{{ route('prescriptions.show', $course->prescription_id) }}" class="font-mono text-xs text-brand-700 hover:underline">{{ $course->prescription->prescription_number }}</a><div class="text-xs text-slate-500">{{ $course->prescription->doctor->name }}</div></td>
                        <td><x-badge :status="$course->status" /></td>
                        <td class="text-right">@include('patients._course-status', ['course' => $course])</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $courses->links() }}
        @endif
    </div>
</x-layouts.app>
