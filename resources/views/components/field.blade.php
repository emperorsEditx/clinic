{{-- Generic form field: <x-field name="phone" label="Phone" /> ; type=select|textarea|date|... --}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'options' => [], 'required' => false, 'help' => null, 'placeholder' => null, 'rows' => 3, 'empty' => '— Select —', 'id' => null])
@php
    $dot = str_replace(['[', ']'], ['.', ''], $name);
    $dot = rtrim($dot, '.');
    $id = $id ?? 'f_'.str_replace(['.', '[', ']'], '_', $name);
    $current = old($dot, $value instanceof \BackedEnum ? $value->value : ($value instanceof \DateTimeInterface ? $value->format($type === 'datetime-local' ? 'Y-m-d\TH:i' : 'Y-m-d') : $value));
    $hasError = $errors->has($dot);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500" aria-hidden="true">*</span>@endif</label>
    @endif
    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'input'.($hasError ? ' input-error' : '')]) }} @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
            @if ($empty !== false)<option value="">{{ $empty }}</option>@endif
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'input'.($hasError ? ' input-error' : '')]) }} @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'input'.($hasError ? ' input-error' : '')]) }} @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
    @endif
    @if ($help && ! $hasError)<p class="help">{{ $help }}</p>@endif
    @error($dot)<p id="{{ $id }}_error" class="error">{{ $message }}</p>@enderror
</div>
