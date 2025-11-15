@props(['disabled' => false])

<button
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center px-3 py-2 border rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out hover:shadow-[0_0_10px_#9ca3af]',
        'data-spinner' => $attributes->get('data-spinner', 'true'),
    ]) }}
>
   
    {{ $slot }}
</button>
