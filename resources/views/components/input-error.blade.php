@props(['messages'])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'text-sm mt-1']) }} style="color: var(--error);">
        @foreach ((array) $messages as $message)
            <p class="m-0">{{ $message }}</p>
        @endforeach
    </div>
@endif
