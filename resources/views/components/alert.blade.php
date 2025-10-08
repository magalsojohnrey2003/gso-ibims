@props([
    'type' => 'info',   // success | info | warning | error
    'message' => null,  // single message
    'title' => null,    // optional title
])

@php
    $styles = [
        'success' => 'bg-green-50 text-green-800 border-green-200',
        'info'    => 'bg-blue-50 text-blue-800 border-blue-200',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'error'   => 'bg-red-50 text-red-800 border-red-200',
    ];
    $icons = [
        'success' => 'fa-solid fa-check-circle',       
        'info'    => 'fa-solid fa-info-circle',       
        'warning' => 'fa-solid fa-exclamation-triangle', 
        'error'   => 'fa-solid fa-times-circle',       
    ];
    $classes = $styles[$type] ?? $styles['info'];
    $icon = $icons[$type] ?? $icons['info'];
@endphp

<div 
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4000)" 
    x-transition:enter="transform transition ease-out duration-500"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-400"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
    class="fixed top-4 right-4 w-96 rounded-xl border p-4 shadow-lg flex items-start gap-3 z-[9999] {{ $classes }}"
>
    <i class="{{ $icon }} text-lg mt-0.5"></i>
    <div class="flex-1 space-y-1">
        @if($title)
            <div class="font-semibold">{{ $title }}</div>
        @endif

        {{-- Single message --}}
        @if($message)
            <div class="text-sm leading-relaxed">{{ $message }}</div>
        @endif

        {{-- Multiple validation errors --}}
        @if($errors->any() && $type === 'error')
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        {{ $slot }}
    </div>

    {{-- Close button --}}
    <button @click="show = false" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors duration-200">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
