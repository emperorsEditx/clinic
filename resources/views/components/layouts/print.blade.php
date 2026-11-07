@props(['title', 'subtitle' => null, 'landscape' => false])
@php $logo = settings()->logoUrl(); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ settings('clinic_name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4 {{ $landscape ? 'landscape' : 'portrait' }}; margin: 14mm; }
        @media print { .page-break { break-before: page; } tr, .avoid-break { break-inside: avoid; } }
        body { background: #fff; }
    </style>
</head>
<body class="text-[12px] text-slate-900">
<div class="mx-auto max-w-4xl p-6 print:p-0">
    <div class="no-print mb-6 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-2">
        <p class="text-xs text-slate-600">Print preview</p>
        <div class="flex gap-2"><button onclick="window.close()" class="btn btn-ghost btn-sm">Close</button><button onclick="window.print()" class="btn btn-primary btn-sm"><x-icon name="printer" /> Print</button></div>
    </div>
    <header class="mb-5 flex items-start justify-between gap-6 border-b-2 border-slate-800 pb-3">
        <div class="flex items-start gap-3">
            @if ($logo)<img src="{{ $logo }}" alt="" class="h-12 w-auto">@endif
            <div>
                <p class="text-base font-bold">{{ settings('clinic_name') }}</p>
                <p class="text-[11px] text-slate-600">{{ collect([settings('clinic_address'), settings('clinic_phone'), settings('clinic_email')])->filter()->implode(' · ') }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-sm font-semibold">{{ $title }}</p>
            @if ($subtitle)<p class="text-[11px] text-slate-600">{{ $subtitle }}</p>@endif
            <p class="text-[10px] text-slate-500">Printed {{ fmt_datetime(now()) }} by {{ auth()->user()->name }}</p>
        </div>
    </header>
    {{ $slot }}
    <footer class="mt-8 border-t border-slate-200 pt-2 text-[10px] text-slate-500">Confidential medical record — {{ settings('clinic_name') }}</footer>
</div>
<script>window.addEventListener('load', () => { if (!location.search.includes('noprint')) setTimeout(() => window.print(), 300); });</script>
</body>
</html>
