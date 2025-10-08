@props([
    'href' => null,   // ✅ Default null prevents "undefined variable" errors
    'color' => 'blue',
    'as' => 'a',
    'type' => 'button',
])

@php
$baseClasses = "inline-flex items-center px-4 py-2 rounded-lg font-semibold text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2";
$colorClasses = match($color) {
    'red' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'green' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'gray' => 'bg-gray-300 text-gray-700 hover:bg-gray-400 focus:ring-gray-400',
    'blue' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    default => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
};
$classes = $baseClasses . ' ' . $colorClasses;
@endphp

@if($as === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@elseif($as === 'button')
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
