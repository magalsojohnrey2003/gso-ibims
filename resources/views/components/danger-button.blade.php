@props(['disabled' => false])

<button
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out hover:shadow-[0_0_10px_#ef4444]',
        'data-spinner' => $attributes->get('data-spinner', 'true'),
    ]) }}
>
    {{ $slot }}
</button>
