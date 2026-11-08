@props(['title' => null])
@php
    $user = auth()->user();
    $nav = [
        ['Dashboard', 'dashboard', 'home', true, 'dashboard'],
        ['Patients', 'patients.index', 'users', $user->can('patients.view'), 'patients.*'],
        ['Appointments', 'appointments.index', 'calendar', $user->can('appointments.view'), 'appointments.*'],
        ['Visits', 'visits.index', 'clipboard', $user->can('visits.view'), 'visits.*'],
        ['Consultations', 'consultations.index', 'stethoscope', $user->can('clinical.view'), 'consultations.*'],
        ['Prescriptions', 'prescriptions.index', 'document', $user->can('clinical.view'), 'prescriptions.*'],
        ['Medications', 'medications.index', 'capsule', $user->can('clinical.view'), 'medications.*'],
        ['Follow-ups', 'follow-ups.index', 'arrow-path', $user->can('clinical.view') || $user->can('appointments.view'), 'follow-ups.*'],
        ['Laboratory', 'laboratory.requests.index', 'beaker', $user->can('lab.view'), 'laboratory.*'],
        ['Reports', 'reports.index', 'chart', $user->can('reports.view'), 'reports.*'],
    ];
    $admin = [
        ['Medicines', 'medicines.index', 'capsule', $user->can('medicines.manage'), 'medicines.*'],
        ['Users', 'users.index', 'users', $user->can('users.manage'), 'users.*'],
        ['Settings', 'settings.edit', 'cog', $user->can('settings.manage'), 'settings.*'],
        ['Audit Log', 'audit.index', 'shield', $user->can('audit.view'), 'audit.*'],
    ];
    $unread = $user->unreadNotifications()->latest()->limit(6)->get();
    $unreadCount = $user->unreadNotifications()->count();
    $logo = settings()->logoUrl();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ settings('clinic_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full" x-data="{ sidebar: false }">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 btn btn-primary">Skip to content</a>

{{-- Mobile sidebar backdrop --}}
<div x-show="sidebar" x-cloak class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" x-on:click="sidebar = false"></div>

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0 no-print" :class="sidebar && 'translate-x-0'" aria-label="Main navigation">
    <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
        <img src="{{ $logo }}" alt="" class="size-8 rounded-md object-contain">
        <div class="min-w-0"><p class="truncate text-[13px] font-semibold text-slate-900">{{ settings('clinic_name') }}</p><p class="text-[11px] text-slate-500">Homoeopathic</p></div>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-0.5">
            @foreach ($nav as [$label, $route, $icon, $allowed, $pattern])
                @if ($allowed)
                    @php $active = request()->routeIs($pattern); @endphp
                    <li><a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition', 'bg-brand-50 text-brand-800' => $active, 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon" class="size-[18px] {{ $active ? 'text-brand-600' : 'text-slate-400' }}" />{{ $label }}</a></li>
                @endif
            @endforeach
        </ul>
        @if (collect($admin)->contains(fn ($i) => $i[3]))
            <p class="mt-6 mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administration</p>
            <ul class="space-y-0.5">
                @foreach ($admin as [$label, $route, $icon, $allowed, $pattern])
                    @if ($allowed)
                        @php $active = request()->routeIs($pattern); @endphp
                        <li><a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition', 'bg-brand-50 text-brand-800' => $active, 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active])><x-icon :name="$icon" class="size-[18px] {{ $active ? 'text-brand-600' : 'text-slate-400' }}" />{{ $label }}</a></li>
                    @endif
                @endforeach
            </ul>
        @endif
    </nav>
    <div class="border-t border-slate-100 p-3 text-[11px] text-slate-400">Signed in as {{ $user->getRoleNames()->map(fn ($r) => \App\Models\User::roleLabels()[$r] ?? $r)->implode(', ') }}</div>
</aside>

<div class="lg:pl-64">
    <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 no-print">
        <button type="button" class="btn btn-ghost -ml-2 lg:hidden" x-on:click="sidebar = true" aria-label="Open navigation"><x-icon name="menu" class="size-5" /></button>

        @can('patients.view')
        <div class="relative w-full max-w-md" x-data="patientSearch({ url: '{{ route('patients.search') }}', navigate: true })" x-on:click.outside="open = false" x-on:keydown.window.slash="if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.input.focus() }">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input x-ref="input" type="search" class="input h-9 bg-slate-50 pl-9 pr-10" placeholder="Search patients by name, ID or phone" aria-label="Search patients" autocomplete="off"
                   x-model="query" x-on:input="search()" x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)"
                   x-on:keydown.enter.prevent="results[active] ? choose(results[active]) : (window.location = '{{ route('patients.index') }}?q=' + encodeURIComponent(query))" x-on:keydown.escape="open = false">
            <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded border border-slate-200 bg-white px-1.5 text-[10px] text-slate-400 sm:block">/</kbd>
            <div x-show="open" x-cloak class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                <template x-for="(r, i) in results" :key="r.id">
                    <a :href="r.url" class="block px-3 py-2 hover:bg-slate-50" :class="i === active && 'bg-slate-50'">
                        <span class="block text-sm font-medium text-slate-900" x-text="r.name"></span>
                        <span class="block text-xs text-slate-500" x-text="r.code + (r.meta ? ' · ' + r.meta : '')"></span>
                    </a>
                </template>
                <p x-show="!results.length && !error" class="px-3 py-3 text-xs text-slate-500">No matching patients.</p>
                <p x-show="error" class="px-3 py-3 text-xs text-red-600" x-text="error"></p>
            </div>
        </div>
        @endcan

        <div class="ml-auto flex items-center gap-1">
            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" class="btn btn-ghost relative" x-on:click="open = !open" aria-label="Notifications" :aria-expanded="open">
                    <x-icon name="bell" class="size-5" />
                    @if ($unreadCount)<span class="absolute right-1.5 top-1.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-semibold text-white">{{ min($unreadCount, 9) }}</span>@endif
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5"><p class="text-[13px] font-semibold">Notifications</p>
                        @if ($unreadCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="text-xs font-medium text-brand-700 hover:underline">Mark all read</button></form>@endif
                    </div>
                    @forelse ($unread as $n)
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf
                            <button class="block w-full border-b border-slate-50 px-4 py-2.5 text-left hover:bg-slate-50"><p class="text-[13px] font-medium text-slate-900">{{ $n->data['title'] ?? 'Notification' }}</p><p class="text-xs text-slate-500">{{ $n->data['message'] ?? '' }}</p><p class="mt-0.5 text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</p></button>
                        </form>
                    @empty
                        <x-empty icon="bell" title="You're all caught up" compact />
                    @endforelse
                    <a href="{{ route('notifications.index') }}" class="block bg-slate-50 px-4 py-2 text-center text-xs font-medium text-slate-600 hover:text-slate-900">View all</a>
                </div>
            </div>

            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100" x-on:click="open = !open" :aria-expanded="open" aria-label="Account menu">
                    <span class="flex size-8 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">{{ $user->initials() }}</span>
                    <span class="hidden text-left sm:block"><span class="block text-[13px] font-medium leading-tight text-slate-900">{{ $user->name }}</span><span class="block text-[11px] text-slate-500">{{ $user->designation ?? \App\Models\User::roleLabels()[$user->getRoleNames()->first()] ?? '' }}</span></span>
                    <x-icon name="chevron-down" class="hidden size-3.5 text-slate-400 sm:block" />
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-[13px] text-slate-700 hover:bg-slate-50"><x-icon name="user" /> My profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 px-4 py-2 text-left text-[13px] text-slate-700 hover:bg-slate-50"><x-icon name="logout" /> Sign out</button></form>
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-800" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" />{{ session('error') }}</div>
        @endif
        @if ($errors->any() && ! session('error'))
            <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-800" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><div>Please correct the highlighted fields.@if ($errors->count() <= 3)<ul class="mt-1 list-disc pl-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif</div></div>
        @endif
        {{ $slot }}
    </main>
</div>

{{-- Toasts --}}
<div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-full max-w-sm flex-col gap-2" x-data x-init="@if(session('success')) $store.toasts.push(@js(session('success'))) @endif" aria-live="polite">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white px-4 py-3 text-[13px] shadow-lg" :class="t.type === 'error' ? 'border-red-200' : 'border-slate-200'" x-transition>
            <span :class="t.type === 'error' ? 'text-red-600' : 'text-emerald-600'"><x-icon name="check-circle" class="size-5" /></span>
            <p class="flex-1 text-slate-700" x-text="t.message"></p>
            <button class="text-slate-400 hover:text-slate-600" x-on:click="$store.toasts.remove(t.id)" aria-label="Dismiss"><x-icon name="x" /></button>
        </div>
    </template>
</div>

{{-- Confirmation dialog --}}
<div x-data x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="alertdialog" aria-modal="true" x-on:keydown.escape.window="$store.confirm.open = false">
    <div class="fixed inset-0 bg-slate-900/40" x-on:click="$store.confirm.open = false"></div>
    <div class="card relative w-full max-w-md p-5 shadow-xl" x-trap.noscroll="$store.confirm.open">
        <div class="flex gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full" :class="$store.confirm.danger ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700'"><x-icon name="warning" class="size-5" /></div>
            <div class="flex-1">
                <h2 class="text-[15px] font-semibold text-slate-900" x-text="$store.confirm.title"></h2>
                <p class="mt-1 text-[13px] text-slate-600" x-text="$store.confirm.message"></p>
                <template x-if="$store.confirm.reasonField">
                    <div class="mt-3"><label class="label" for="confirm_reason">Reason <span class="text-red-500">*</span></label><textarea id="confirm_reason" class="input" rows="2" x-model="$store.confirm.reason" maxlength="255"></textarea></div>
                </template>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="btn btn-secondary" x-on:click="$store.confirm.open = false">Cancel</button>
            <button type="button" class="btn" :class="$store.confirm.danger ? 'btn-danger' : 'btn-primary'" x-on:click="$store.confirm.accept()" :disabled="$store.confirm.reasonField && !$store.confirm.reason.trim()" x-text="$store.confirm.confirmLabel"></button>
        </div>
    </div>
</div>
</body>
</html>
