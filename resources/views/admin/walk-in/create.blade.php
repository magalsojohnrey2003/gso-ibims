{{-- resources/views/admin/walk-in/create.blade.php --}}
<x-app-layout>
    <x-title
        level="h2"
        size="2xl"
        weight="bold"
        icon="clipboard-document-check"
        variant="s"
        iconStyle="circle"
        iconBg="gov-accent"
        iconColor="white">
        Create Walk-in Borrow
    </x-title>

    <div class="p-6 max-w-7xl mx-auto">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('admin.walkin.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i> Back to Walk-in List
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Items selection table -->
            <section class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-boxes-stacked text-purple-600"></i>
                        Select Items
                    </h3>
                    <div class="relative w-full sm:w-80">
                        <input id="walkinSearch" type="text" placeholder="Search items..."
                               class="w-full rounded-lg border border-gray-300 pl-10 pr-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" />
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <div class="overflow-auto max-h-[60vh] rounded-xl border border-gray-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Item</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Available</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="walkinItemsBody" class="divide-y divide-gray-100">
                            @foreach($items as $item)
                                @php
                                    $photoUrl = null;
                                    if (!empty($item->photo)) {
                                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($item->photo)) {
                                            $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($item->photo);
                                        } elseif (str_starts_with($item->photo, 'http')) {
                                            $photoUrl = $item->photo;
                                        } elseif (file_exists(public_path($item->photo))) {
                                            $photoUrl = asset($item->photo);
                                        }
                                    }
                                    if (!$photoUrl) { $photoUrl = asset($defaultPhoto); }
                                @endphp
                                <tr data-item-row data-name="{{ strtolower($item->name) }}" class="align-middle">
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $photoUrl }}" class="h-12 w-12 rounded object-cover" alt="{{ $item->name }}" />
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                                <p class="text-xs text-gray-500">Category: {{ $item->category }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 text-xs font-semibold px-2 py-1">
                                            {{ (int)($item->available_qty ?? 0) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        @php $max = max(0, (int)($item->available_qty ?? 0)); @endphp
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="0" max="{{ $max }}" value="0"
                                                   data-qty-input data-item-id="{{ $item->id }}"
                                                   class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-sm text-gray-800 focus:ring-purple-500 focus:border-purple-500"
                                                   aria-label="Quantity for {{ $item->name }}" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Walk-in details sidebar -->
            <aside class="lg:col-span-1 bg-white rounded-2xl shadow-md border border-gray-200 p-4 lg:sticky lg:top-6 h-max">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-id-card text-purple-600"></i>
                    Walk-in Details
                </h3>

                <form id="walkinForm" class="space-y-4" method="POST" action="{{ route('admin.walkin.store') }}">
                    @csrf
                    <div>
                        <x-input-label for="borrower_name" value="Borrower's Name" />
                        <x-text-input id="borrower_name" name="borrower_name" type="text" maxlength="255" class="mt-1 w-full" />
                    </div>

                    <div>
                        <x-input-label for="office_agency" value="Request Office/Agency" />
                        <x-text-input id="office_agency" name="office_agency" type="text" maxlength="255" class="mt-1 w-full" />
                    </div>

                    <div>
                        <x-input-label for="contact_number" value="Contact Number" />
                        <x-text-input id="contact_number" name="contact_number" type="text" maxlength="50" inputmode="numeric" pattern="[0-9]*" class="mt-1 w-full" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Address" />
                        <x-text-input id="address" name="address" type="text" maxlength="500" class="mt-1 w-full" />
                    </div>

                    <div>
                        <x-input-label for="purpose" value="Purpose" />
                        <textarea id="purpose" name="purpose" rows="3" maxlength="500" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:ring-purple-500 focus:border-purple-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="borrowed_date" value="Borrowed Date &amp; Time" />
                            <div class="mt-1 flex flex-col gap-2">
                                <input id="borrowed_date" name="borrowed_date" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" />
                                <input id="borrowed_time" name="borrowed_time" type="time" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" placeholder="Select time (optional)" />
                                <p class="text-xs text-gray-500">Time is optional.</p>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="returned_date" value="Returned Date &amp; Time" />
                            <div class="mt-1 flex flex-col gap-2">
                                <input id="returned_date" name="returned_date" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" />
                                <input id="returned_time" name="returned_time" type="time" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" placeholder="Select time (optional)" />
                                <p class="text-xs text-gray-500">Time is optional.</p>
                            </div>
                        </div>
                    </div>

                    <div id="timeUsagePreview" class="hidden rounded-lg border border-purple-100 bg-purple-50 px-3 py-2 text-sm text-purple-700">
                        <span class="font-medium">Time of Usage Preview:</span>
                        <span data-preview-range>—</span>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                        <p class="text-gray-600">Selected items: <span id="selectedCount" class="font-semibold">0</span></p>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <x-secondary-button type="button" id="walkinClearBtn">Clear</x-secondary-button>
                        <x-button type="button" id="walkinSubmitBtn" iconName="paper-airplane">Create Walk-in Request</x-button>
                    </div>
                </form>
            </aside>
        </div>
    </div>

    @vite(['resources/js/admin-walk-in.js'])
</x-app-layout>
