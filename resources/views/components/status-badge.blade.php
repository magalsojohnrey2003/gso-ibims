@props([ 
    'type' => 'info',   // success | info | delivered | warning | danger | gray | accepted | rejected | pending
    'text' => '',
    'icon' => null,
])

@php
    // Define styles with modern colors
    $styles = [
        'success'  => 'bg-green-100 text-green-800 ring-green-200',
        'info'     => 'bg-blue-100 text-blue-800 ring-blue-200',
        'delivered'=> 'bg-blue-100 text-blue-800 ring-blue-200',
        'warning'  => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'danger'   => 'bg-red-100 text-red-800 ring-red-200',
        'gray'     => 'bg-gray-100 text-gray-800 ring-gray-200',
        'accepted' => 'bg-green-100 text-green-800 ring-green-200',
        'rejected' => 'bg-red-100 text-red-800 ring-red-200',
        'pending'  => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'qr'       => 'bg-teal-100 text-teal-800 ring-teal-200',
    ];

    // Define icons for each status type
    $icons = [
        'success'  => 'fa-check-circle',
        'info'     => 'fa-info-circle',
        'delivered'=> 'fa-truck',
        'warning'  => 'fa-clock',
        'danger'   => 'fa-exclamation-triangle',
        'gray'     => 'fa-question-circle',
        'accepted' => 'fa-check-circle',
        'rejected' => 'fa-times-circle',
        'pending'  => 'fa-clock',
        'qr'       => 'fa-qrcode',
    ];

    // Determine which style and icon to use
    $classes = $styles[$type] ?? $styles['info'];
    $icon = $icon ?? ($icons[$type] ?? $icons['info']);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset $classes"]) }}>
    <i class="fas {{ $icon }} text-xs"></i>
    <span>{{ $text ?: ucfirst($type) }}</span>
</span>
