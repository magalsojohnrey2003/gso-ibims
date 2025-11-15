@php
    $primaryInstance = $item->instances->first();
    $photoUrl = $item->photo_url;
    $hasUploadedPhoto = filled($item->photo ?? $primaryInstance?->photo);

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
                <div class="w-full h-40 rounded-lg bg-white border border-gray-200 flex flex-col items-center justify-center overflow-hidden shadow">
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $item->name }} photo"
                        class="w-full h-full object-cover">
                    @unless($hasUploadedPhoto)
                        <span class="text-xs text-gray-500 py-1 bg-white/80 w-full text-center">Default placeholder shown</span>
                    @endunless
                </div>
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
            <h4 class="text-lg font-semibold text-gray-900">Property Numbers</h4>
            <span class="text-xs text-gray-500">{{ $item->instances->count() }} item{{ $item->instances->count() === 1 ? '' : 's' }}</span>
        </div>

        <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
            <div class="table-container-no-scroll" style="max-height: 18rem;">
                <table class="w-full text-sm text-center text-gray-600 gov-table">
                    <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                        <tr>
                            <th class="px-6 py-3 whitespace-nowrap">Property Numbers</th>
                            <th class="px-6 py-3">Serial No.</th>
                            <th class="px-6 py-3">Model No.</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">History</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @forelse($item->instances as $instance)
                            <tr class="hover:bg-gray-50" data-item-instance-id="{{ $instance->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">{{ $instance->property_number }}</td>
                                <td class="px-6 py-4">
                                    @php $sn = $instance->serial_no ?? '—'; @endphp
                                    <span class="inline-block max-w-[3rem] truncate align-middle cursor-help" title="{{ $sn }}">{{ $sn }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @php $mn = $instance->model_no ?? '—'; @endphp
                                    <span class="inline-block max-w-[3rem] truncate align-middle cursor-help" title="{{ $mn }}">{{ $mn }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $status = strtolower($instance->status ?? 'unknown');
                                        $statusClasses = [
                                            'available' => 'bg-green-100 text-green-700',
                                            'borrowed' => 'bg-indigo-100 text-indigo-700',
                                            'returned' => 'bg-emerald-100 text-emerald-700',
                                            'missing' => 'bg-red-100 text-red-700',
                                            'damaged' => 'bg-amber-100 text-amber-700',
                                            'minor_damage' => 'bg-yellow-100 text-yellow-700',
                                            'pending' => 'bg-gray-200 text-gray-700',
                                        ];
                                        $statusIcons = [
                                            'available' => 'fa-check-circle',
                                            'borrowed' => 'fa-box',
                                            'returned' => 'fa-arrow-left',
                                            'missing' => 'fa-exclamation-triangle',
                                            'damaged' => 'fa-exclamation-triangle',
                                            'minor_damage' => 'fa-exclamation-circle',
                                            'pending' => 'fa-clock',
                                            'unknown' => 'fa-question-circle',
                                        ];
                                        $badgeClass = $statusClasses[$status] ?? 'bg-gray-200 text-gray-700';
                                        $badgeIcon = $statusIcons[$status] ?? 'fa-question-circle';
                                        $badgeBase = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold';
                                        $statusLabel = ucwords(str_replace('_', ' ', $status));
                                    @endphp
                                    <span class="{{ $badgeBase }} {{ $badgeClass }}" data-instance-status data-badge-base="{{ $badgeBase }}">
                                        <i class="fas {{ $badgeIcon }} text-xs"></i>
                                        <span>{{ $statusLabel }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <x-button
                                        variant="secondary"
                                        size="sm"
                                        class="h-9 w-9 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                        iconName="clock"
                                        x-data
                                        x-on:click.prevent="window.dispatchEvent(new CustomEvent('item-history:open', { detail: { instanceId: {{ $instance->id }}, propertyNumber: '{{ $instance->property_number }}' } }))">
                                        History
                                    </x-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-500">
                                    No Property Numbers are currently linked to this item.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
