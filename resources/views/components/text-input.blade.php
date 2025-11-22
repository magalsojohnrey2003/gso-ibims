@props([
    'disabled' => false,
])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'gov-input block w-full px-3 py-2 text-sm leading-tight rounded-xl transition duration-200 ease-out focus:outline-none focus:ring-0 placeholder:text-gray-500 dark:placeholder:text-gray-300'
]) !!}>
