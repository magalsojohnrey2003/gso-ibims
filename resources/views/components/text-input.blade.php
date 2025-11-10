@props([
    'disabled' => false,
])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'block w-full px-3 py-2 text-sm border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200'
]) !!}>
