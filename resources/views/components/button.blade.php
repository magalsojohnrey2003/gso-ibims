{{-- resources/views/components/button.blade.php --}}
@props([
    'variant' => 'primary',     // primary | secondary | danger | success
    'size' => 'md',             // sm | md | lg
    'iconPosition' => 'left',   // left | right
    'as' => 'button',           // button | a
    'href' => null,             // when as="a"
    'type' => 'button',         // button | submit | reset (when as="button")
    'disabled' => false,
    'iconName' => null,         // e.g. "trash", "pencil-square"
    'iconStyle' => 'o',         // o = outline, s = solid
])

@php
    use Illuminate\Support\Str;

    $variantClasses = [
        'primary'   => 'text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:ring-indigo-500 hover:shadow-[0_0_10px_#6366f1] border border-transparent',
        'secondary' => 'text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-indigo-500 hover:shadow-[0_0_10px_#9ca3af] border border-gray-300 dark:border-gray-600',
        'danger'    => 'text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:ring-red-500 hover:shadow-[0_0_10px_#ef4444] border border-transparent',
        'success'   => 'text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 focus:ring-green-500 hover:shadow-[0_0_10px_#22c55e] border border-transparent',
    ];

    $sizeClasses = [
        'sm' => 'px-2.5 py-1.5 text-sm',
        'md' => 'px-3 py-2 text-sm',
        'lg' => 'px-4 py-2.5 text-base',
    ];

    $base = 'inline-flex items-center justify-center rounded-md shadow-sm font-medium transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $isActionButton = Str::contains($attributes->get('class', ''), 'btn-action');

    if ($isActionButton) {
        $base = 'inline-flex items-center justify-center rounded-full shadow-sm font-medium transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#6F42C1] disabled:opacity-60 disabled:cursor-not-allowed relative overflow-visible aspect-square border border-gray-200 bg-white text-[#6F42C1]';
        $sizeClasses = [
            'sm' => 'p-2',
            'md' => 'p-2.5',
            'lg' => 'p-3',
        ];
        $variantClasses = array_fill_keys(array_keys($variantClasses), '');
    }

    $classes = implode(' ', array_filter([
        $base,
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $variantClasses[$variant] ?? $variantClasses['primary'],
    ]));

    $tag = $as === 'a' ? 'a' : 'button';

    // Build the Heroicon component name if iconName is provided
    $iconComponent = $iconName ? "heroicon-{$iconStyle}-{$iconName}" : null;

    $iconLeftMargin = $isActionButton ? '' : 'mr-2';
    $iconRightMargin = $isActionButton ? '' : 'ml-2';
@endphp

<{{ $tag }}
    @if($tag === 'a') href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => $classes,
        'data-spinner' => $attributes->get('data-spinner', 'true'),
    ]) }}
>
    {{-- Left icon (slot takes priority; if not provided, use iconName) --}}
    @if (($iconPosition ?? 'left') === 'left')
        @if (isset($icon))
            @if ($isActionButton)
                {{ $icon }}
            @else
                <span class="{{ $iconLeftMargin }}">{{ $icon }}</span>
            @endif
        @elseif ($iconComponent)
            @if ($isActionButton)
                <x-dynamic-component :component="$iconComponent" class="w-5 h-5" />
            @else
                <span class="{{ $iconLeftMargin }}">
                    <x-dynamic-component :component="$iconComponent" class="w-5 h-5" />
                </span>
            @endif
        @endif
    @endif

    <span>{{ $slot }}</span>

    {{-- Right icon --}}
    @if (($iconPosition ?? 'left') === 'right')
        @if (isset($icon))
            @if ($isActionButton)
                {{ $icon }}
            @else
                <span class="{{ $iconRightMargin }}">{{ $icon }}</span>
            @endif
        @elseif ($iconComponent)
            @if ($isActionButton)
                <x-dynamic-component :component="$iconComponent" class="w-5 h-5" />
            @else
                <span class="{{ $iconRightMargin }}">
                    <x-dynamic-component :component="$iconComponent" class="w-5 h-5" />
                </span>
            @endif
        @endif
    @endif
</{{ $tag }}>
