@props(['status' => null, 'color' => null])
@php
    $label = $status instanceof \BackedEnum ? $status->label() : ($status ?? $slot);
    $c = $color ?? ($status instanceof \BackedEnum && method_exists($status, 'color') ? $status->color() : 'slate');
@endphp
<span {{ $attributes->merge(['class' => 'badge badge-'.$c]) }}>{{ $status !== null ? $label : $slot }}</span>
