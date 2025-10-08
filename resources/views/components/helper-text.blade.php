@props(['type' => 'info'])

@php
    $colors = [
        'info' => 'text-blue-600',
        'warning' => 'text-yellow-600',
        'error' => 'text-red-600',
        'success' => 'text-green-600',
    ];

    $icons = [
        'info' => 'M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z',
        'warning' => 'M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z',
        'error' => 'M6 18L18 6M6 6l12 12',
        'success' => 'M5 13l4 4L19 7',
    ];

    $color = $colors[$type] ?? $colors['info'];
    $iconPath = $icons[$type] ?? $icons['info'];
@endphp

<div class="flex items-start space-x-2 text-sm {{ $color }}">
    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
    </svg>
    <span>{{ $slot }}</span>
</div>
