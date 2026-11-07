@php
    $selectedRoles = old('roles', $user->exists ? $user->getRoleNames()->all() : []);
    $self = $user->exists && $user->id === auth()->id();
@endphp
<div class="space-y-6">
    <section class="card">
        <div class="card-header"><h2 class="card-title">Account</h2></div>
        <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-field name="name" label="Full name" required :value="$user->name" />
            <x-field name="email" label="Email" type="email" required :value="$user->email" autocomplete="off" />
            <x-field name="phone" label="Phone" :value="$user->phone" />
            <x-field name="designation" label="Designation" :value="$user->designation" placeholder="e.g. Consultant Physician" />
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h2 class="card-title">Roles</h2></div>
        <div class="card-body">
            <fieldset><legend class="sr-only">Roles</legend>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach (App\Models\User::roleLabels() as $value => $label)
                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <input type="checkbox" name="roles[]" value="{{ $value }}" @checked(in_array($value, $selectedRoles, true)) class="mt-0.5 rounded border-slate-300 text-brand-600">
                            <span><span class="block text-[13px] font-medium">{{ $label }}</span><span class="block text-xs text-slate-500">{{ match ($value) {
                                'administrator' => 'Users, settings, reports, all records (read-only clinical access)',
                                'doctor' => 'Consultations, prescriptions, repeat authorisation, lab requests and review',
                                'receptionist' => 'Registration, appointments, visits — no clinical records',
                                'lab_technician' => 'Lab requests, status updates, report uploads',
                                default => '' } }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @error('roles')<p class="error">{{ $message }}</p>@enderror
            @if ($self)<p class="help mt-2">You cannot remove your own administrator role.</p>@endif
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h2 class="card-title">Professional details</h2><span class="text-xs text-slate-500">Printed on prescriptions for doctors</span></div>
        <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-3">
            <x-field name="qualifications" label="Qualifications" :value="$user->qualifications" placeholder="e.g. MBBS, FCPS" />
            <x-field name="specialization" label="Specialisation" :value="$user->specialization" />
            <x-field name="registration_number" label="Registration number" :value="$user->registration_number" />
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h2 class="card-title">{{ $user->exists ? 'Reset password' : 'Password' }}</h2></div>
        <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-field name="password" label="Password" type="password" :required="! $user->exists" autocomplete="new-password" :help="$user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters with letters and numbers.'" />
            <x-field name="password_confirmation" label="Confirm password" type="password" :required="! $user->exists" autocomplete="new-password" />
            @unless ($self)
                <label class="flex items-center gap-2 text-[13px] md:col-span-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-slate-300"> Account active (can sign in)</label>
            @endunless
        </div>
    </section>
</div>
