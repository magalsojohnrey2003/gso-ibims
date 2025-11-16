@props(['disabled' => false])

<button
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out hover:shadow-[0_0_10px_#6366f1]',
        'data-spinner' => $attributes->get('data-spinner', 'true'),
    ]) }}
>
    {{ $slot }}
</button>
