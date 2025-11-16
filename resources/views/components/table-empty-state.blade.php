@props([
    'colspan' => 1,
    'title' => 'No Records Found',
    'description' => 'There are no records to display at this time.',
])

<tr {{ $attributes->class('bg-white') }}>
    <td colspan="{{ (int) $colspan }}" class="table-state-cell text-center py-10 px-4">
        <div class="flex flex-col items-center justify-center text-center text-gray-500">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-7 4h8m-9 4h10m2 4H5a2 2 0 0 1-2-2V7c0-1.1.9-2 2-2h14a2 2 0 0 1 2 2v8c0 1.1-.9 2-2 2z" />
                </svg>
            </span>
            <p class="mt-4 text-base font-semibold text-gray-800">{{ $title }}</p>
            <p class="text-sm text-gray-500">{{ $description }}</p>
        </div>
    </td>
</tr>
