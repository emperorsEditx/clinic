<x-layouts.guest title="Sign in">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            @if ($logo = settings()->logoUrl())
                <img src="{{ $logo }}" alt="{{ settings('clinic_name') }}" class="mx-auto mb-4 h-12 object-contain">
            @else
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-brand-600 text-white"><x-icon name="heart" class="size-6" /></div>
            @endif
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ settings('clinic_name') }}</h1>
            <p class="mt-1 text-[13px] text-slate-500">Sign in to the clinic management system</p>
        </div>
        <div class="card p-6">
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <x-field name="email" label="Email" type="email" required autofocus autocomplete="username" />
                <x-field name="password" label="Password" type="password" required autocomplete="current-password" />
                <label class="flex items-center gap-2 text-[13px] text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Keep me signed in</label>
                <button class="btn btn-primary w-full py-2.5">Sign in</button>
            </form>
        </div>
        <p class="mt-6 text-center text-xs text-slate-400">Forgot your password? Ask a clinic administrator to reset it.</p>
    </div>
</x-layouts.guest>
