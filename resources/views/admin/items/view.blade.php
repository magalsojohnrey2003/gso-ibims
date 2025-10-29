@php
    $primaryInstance = $item->instances->first();
    $photoPath = $item->photo ?? $primaryInstance?->photo ?? null;
    $photoUrl = '';
    if ($photoPath) {
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($photoPath)) {
            $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($photoPath);
        } elseif (str_starts_with($photoPath, 'http')) {
            $photoUrl = $photoPath;
        }
    }

    $description = $primaryInstance?->notes;
    $categoryLabel = $displayCategory ?? ($item->category ?? '');
    $updatedAt = optional($item->updated_at)->format('M d, Y g:i A');
    $createdAt = optional($item->created_at)->format('M d, Y g:i A');
    $acquisitionDateDisplay = optional($item->acquisition_date)->format('M d, Y');
    $acquisitionCostDisplay = $item->acquisition_cost !== null
        ? '₱' . number_format($item->acquisition_cost, 0)
        : null;
@endphp

<div class="space-y-8">
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">
                <i class="fas fa-hashtag"></i>
                ID: {{ $item->id }}
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr,280px]">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Item Name</span>
                    <p class="text-base font-semibold text-gray-900">{{ $item->name }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Category</span>
                    <p class="text-base font-semibold text-gray-900">{{ $categoryLabel ?: '—' }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Total Quantity</span>
                    <p class="text-base font-semibold text-gray-900">{{ $item->total_qty }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Available</span>
                    <p class="text-base font-semibold {{ $item->available_qty > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $item->available_qty }}
                    </p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Acquisition Date</span>
                    <p class="text-base font-medium text-gray-900">{{ $acquisitionDateDisplay ?: '—' }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Acquisition Cost</span>
                    <p class="text-base font-medium text-gray-900">{{ $acquisitionCostDisplay ?: '—' }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Created</span>
                    <p class="text-base font-medium text-gray-900">{{ $createdAt ?: '—' }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Last Updated</span>
                    <p class="text-base font-medium text-gray-900">{{ $updatedAt ?: '—' }}</p>
                </div>
            </div>

            <div class="flex justify-center items-center">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 w-full max-w-[240px]">
                    @if($photoUrl)
                        <img
                            src="{{ $photoUrl }}"
                            alt="{{ $item->name }} photo"
                            class="w-full h-40 object-cover rounded-lg shadow-md">
                    @else
                        <div class="w-full h-40 rounded-lg bg-white border border-dashed border-gray-300 flex items-center justify-center">
                            <span class="text-sm text-gray-500">No photo uploaded</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($description)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <span class="block text-xs uppercase tracking-wide text-gray-500 mb-2">Description / Notes</span>
                <p class="text-sm leading-relaxed text-gray-700 whitespace-pre-line">{{ $description }}</p>
            </div>
        @endif
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="text-lg font-semibold text-gray-900">Item Record Table</h4>
            <span class="text-xs text-gray-500">Showing {{ $item->instances->count() }} record{{ $item->instances->count() === 1 ? '' : 's' }}</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="max-h-72 overflow-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold sticky left-0 bg-gray-50">Property Numbers</th>
                            <th class="px-4 py-3 text-left font-semibold">Serial No.</th>
                            <th class="px-4 py-3 text-left font-semibold">Model No.</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($item->instances as $instance)
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3 font-semibold text-gray-900 sticky left-0 bg-white">{{ $instance->property_number }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $instance->serial_no ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $instance->model_no ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $instance->status === 'available' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($instance->status ?? 'unknown') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                    No Property Numberss are currently linked to this item.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
