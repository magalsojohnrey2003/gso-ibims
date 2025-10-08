@props(['id' => 'dynamicAlert', 'type' => 'error'])

<div id="{{ $id }}" 
     class="hidden px-4 py-2 rounded mb-4 text-sm
        {{ $type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
</div>
