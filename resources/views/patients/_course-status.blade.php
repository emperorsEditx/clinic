{{-- Status changer for a medication course. Needs $course --}}
@can('update', $course)
    <div x-data="{ open: false }" class="relative inline-block no-print" x-on:click.outside="open = false">
        <button type="button" class="btn btn-ghost btn-sm" x-on:click="open = !open" :aria-expanded="open" aria-label="Change status of {{ $course->medicine_name }}">Change <x-icon name="chevron-down" class="size-3" /></button>
        <div x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-64 rounded-xl border border-slate-200 bg-white p-3 text-left shadow-lg">
            <form method="POST" action="{{ route('medications.status', $course) }}" class="space-y-2">
                @csrf
                <label class="label" for="cs_{{ $course->id }}">New status</label>
                <select id="cs_{{ $course->id }}" name="status" class="input">
                    @foreach (App\Enums\MedicationCourseStatus::cases() as $s)
                        @if ($s !== $course->status)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endif
                    @endforeach
                </select>
                <label class="label" for="cr_{{ $course->id }}">Reason</label>
                <input id="cr_{{ $course->id }}" name="reason" class="input" maxlength="255" placeholder="Required when discontinuing">
                <button class="btn btn-primary btn-sm w-full">Update</button>
            </form>
        </div>
    </div>
@endcan
