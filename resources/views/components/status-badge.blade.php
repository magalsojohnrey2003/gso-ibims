@props([ 
    'type' => 'info',   // success | info | warning | danger | gray | accepted | rejected | pending
    'text' => '',
])

@php
    $styles = [
        'success'  => 'bg-green-100 text-green-800 ring-green-200',
        'info'     => 'bg-blue-100 text-blue-800 ring-blue-200',
        'warning'  => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'danger'   => 'bg-red-100 text-red-800 ring-red-200',
        'gray'     => 'bg-gray-100 text-gray-800 ring-gray-200',
        'accepted' => 'bg-green-200 text-green-900 ring-green-300',
        'rejected' => 'bg-red-200 text-red-900 ring-red-300',
        'pending'  => 'bg-yellow-200 text-yellow-900 ring-yellow-300',
    ];

    // Determine which style to use
    $classes = $styles[$type] ?? $styles['info'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset $classes"]) }}>
    {{ $text ?: ucfirst($type) }}
</span>
