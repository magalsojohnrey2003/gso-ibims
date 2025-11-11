@props(['messages'])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'text-sm mt-1 text-red-600 dark:text-red-400']) }}>
        @foreach ((array) $messages as $message)
            <p class="m-0">{{ $message }}</p>
        @endforeach
    </div>
@endif
