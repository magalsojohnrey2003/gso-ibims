{{-- resources/views/components/title.blade.php --}}
@props([
    'level'     => 'h1',
    'icon'      => null,
    'variant'   => 's',      // 's' solid, 'o' outline
    'size'      => '2xl',
    'weight'    => 'bold',
    'iconSize'  => '6',
    'iconColor' => 'gov-accent',   // token (see mapping below)
    'iconStyle' => 'plain',        // 'plain' | 'circle'
    'iconBg'    => 'transparent',  // token used for circle bg
    'iconLabel' => null,
])

@php
    // size & weight maps (purge-safe)
    $sizeMap = [
        'xs'   => 'text-xs',
        'sm'   => 'text-sm',
        'base' => 'text-base',
        'lg'   => 'text-lg',
        'xl'   => 'text-xl',
        '2xl'  => 'text-2xl',
        '3xl'  => 'text-3xl',
    ];
    $weightMap = [
        'normal'    => 'font-normal',
        'semibold'  => 'font-semibold',
        'bold'      => 'font-bold',
        'extrabold' => 'font-extrabold',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['2xl'];
    $weightClass = $weightMap[$weight] ?? $weightMap['bold'];

    $headingBase = trim("flex items-center gap-3 {$sizeClass} {$weightClass} leading-tight");

    // Map commonly used tokens to utility classes (safe for editor + purge)
    $colorTokenToClass = [
        'white'      => 'text-white',
        'black'      => 'text-black',
        'gov-accent' => 'text-gov-accent',
        'muted'      => 'text-muted',       // optional: if you add it to css
        // allow direct tailwind tokens like 'gray-600' -> 'text-gray-600' by convention
    ];

    $bgTokenToClass = [
        'white'      => 'bg-white',
        'black'      => 'bg-black',
        'gov-accent' => 'bg-gov-accent',
        'transparent'=> 'bg-transparent',
    ];

    // helper: convert token to text class if exists or to text-{token} for simple tailwind-like tokens
    $toTextClass = function ($token) use ($colorTokenToClass) {
        if (!$token) return '';
        if (isset($colorTokenToClass[$token])) return $colorTokenToClass[$token];
        // if token looks like tailwind color (e.g. gray-600), return text-gray-600
        if (preg_match('/^[a-z]+-[0-9]{3}$/', $token)) {
            return 'text-' . $token;
        }
        // fallback: no class (editor-safe)
        return '';
    };

    $toBgClass = function ($token) use ($bgTokenToClass) {
        if (!$token) return '';
        if (isset($bgTokenToClass[$token])) return $bgTokenToClass[$token];
        if (preg_match('/^[a-z]+-[0-9]{3}$/', $token)) {
            return 'bg-' . $token;
        }
        return '';
    };

    // final classes
    $iconSizeClass = "w-{$iconSize} h-{$iconSize}";
    $iconColorClass = $toTextClass($iconColor) ?: 'text-gray-700';
    $iconBgClass = $toBgClass($iconBg) ?: 'bg-transparent';

    // wrapper classes (circle vs plain)
    if ($icon && $iconStyle === 'circle') {
        // circle: padding, rounded-full and background class
        $iconWrapperClass = trim("inline-flex items-center justify-center p-2 rounded-full {$iconBgClass}");
        // when circle, icon itself should use text color class
        $iconInnerClass = $iconColorClass;
    } else {
        // plain: small margin, icon colored via iconColorClass
        $iconWrapperClass = "inline-flex items-center justify-center mr-1";
        $iconInnerClass = $iconColorClass;
    }

    // heroicon component name (blade-heroicons v2 style)
    $componentName = $icon ? ('heroicon-' . ($variant === 's' ? 's' : 'o') . '-' . $icon) : null;
@endphp

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4']) }}>
    {{-- Heading --}}
    <{{ $level }} class="{{ $headingBase }} text-current">
        @if($icon && $componentName)
            <span class="{{ $iconWrapperClass }}" @if($iconLabel) role="img" aria-label="{{ $iconLabel }}" @else aria-hidden="true" @endif>
                {{-- dynamic heroicon inserted here, uses classes for color & size --}}
                <x-dynamic-component :component="$componentName" class="{{ $iconSizeClass }} {{ $iconInnerClass }}" />
            </span>
        @endif

        <span class="leading-tight ml-1 text-current">
            {{ $slot }}
        </span>
    </{{ $level }}>

    {{-- optional actions slot (button group etc) --}}
    @if (isset($actions) && trim($actions) !== '')
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
