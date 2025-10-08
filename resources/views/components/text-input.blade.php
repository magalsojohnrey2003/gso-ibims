@props([
    'disabled' => false,
])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    // default to your theme-aware input class; additional classes may be merged by callers
    'class' => 'input-field'
]) !!}>
