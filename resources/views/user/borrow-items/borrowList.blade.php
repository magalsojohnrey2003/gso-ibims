{{-- resources/views/user/borrow-items/borrowList.blade.php --}}
@php
    $municipalities = config('locations.municipalities', []);
    $oldLocation = old('location', optional($borrowRequest ?? null)->location ?? '');
    $locationPieces = array_values(array_filter(array_map('trim', explode(',', $oldLocation))));
    $oldMunicipalityLabel = $locationPieces[0] ?? null;
    $oldBarangay = $locationPieces[1] ?? null;
    $oldPurok = $locationPieces[2] ?? null;
    $oldMunicipalityKey = collect($municipalities)
        ->filter(fn ($definition) => ($definition['label'] ?? null) === $oldMunicipalityLabel)
        ->keys()
        ->first();

    $usageOptions = [];
    // Build 30-minute intervals from 06:00 to 22:00 inclusive
    for ($hour = 6; $hour <= 22; $hour++) {
        foreach ([0, 30] as $minute) {
            $value = sprintf('%02d:%02d', $hour, $minute);
            $usageOptions[$value] = \Illuminate\Support\Carbon::createFromTime($hour, $minute)->format('g:i A');
        }
    }

    $usageKeys = array_keys($usageOptions);
    // Make time optional by default. Use saved value if present; otherwise blank.
    $savedUsageRange = old('time_of_usage', optional($borrowRequest ?? null)->time_of_usage);
    $usageStart = null;
    $usageEnd = null;
    if ($savedUsageRange && str_contains($savedUsageRange, '-')) {
        [$tmpStart, $tmpEnd] = array_pad(explode('-', $savedUsageRange), 2, null);
        if (in_array($tmpStart, $usageKeys, true)) {
            $usageStart = $tmpStart;
        }
        if (in_array($tmpEnd, $usageKeys, true)) {
            $usageEnd = $tmpEnd;
        }
        if ($usageStart && $usageEnd) {
            $startIndex = array_search($usageStart, $usageKeys, true);
            $endIndex = array_search($usageEnd, $usageKeys, true);
            if ($endIndex !== false && $startIndex !== false && $endIndex <= $startIndex) {
                $endIndex = min($startIndex + 1, count($usageKeys) - 1);
                $usageEnd = $usageKeys[$endIndex];
            }
        }
    }

    $defaultUsageRange = ($usageStart && $usageEnd) ? "{$usageStart}-{$usageEnd}" : '';
    $usageCurrentLabel = ($usageStart && $usageEnd)
        ? ("{$usageOptions[$usageStart]} - {$usageOptions[$usageEnd]}")
        : '--';

    $oldBorrowDateValue = old('borrow_date', optional($borrowRequest ?? null)->borrow_date ?? null);
    $oldReturnDateValue = old('return_date', optional($borrowRequest ?? null)->return_date ?? null);

    $usageBorrowDisplayDefault = $oldBorrowDateValue
        ? \Illuminate\Support\Carbon::parse($oldBorrowDateValue)->format('M. j, Y')
        : 'Select on calendar';
    $usageReturnDisplayDefault = $oldReturnDateValue
        ? \Illuminate\Support\Carbon::parse($oldReturnDateValue)->format('M. j, Y')
        : 'Select on calendar';
@endphp

<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-10 py-6 space-y-6">
         <x-title
            level="h2"
            size="2xl"
            weight="bold"
            icon="shopping-cart"
            variant="s"
            iconColor="title-purple">
            Borrow List
        </x-title>
        @if(session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        @if(session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif
        @if($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <form id="borrowListForm"
              action="{{ route('borrowList.submit') }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-8">
            @csrf

            <div class="bg-white p-5 rounded-2xl shadow-md">
                <ol id="borrowWizardIndicator" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <li data-step-index="1" class="flex items-center gap-3 rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-sm font-medium text-purple-700">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white">1</span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-purple-500">Step 1</p>
                            <p>Items &amp; Allocation</p>
                        </div>
                    </li>
                    <li data-step-index="2" class="flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-600">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-700">2</span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Step 2</p>
                            <p>Schedule</p>
                        </div>
                    </li>
                    <li data-step-index="3" class="flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-600">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-700">3</span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Step 3</p>
                            <p>Letter &amp; Review</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div id="borrowWizardSteps" class="space-y-8">
                {{-- Step 1 --}}
                <section data-step="1" class="wizard-step space-y-6">
                    <div class="grid gap-6 xl:grid-cols-3 items-start">
                        <div class="bg-white p-6 rounded-2xl shadow-lg h-full flex flex-col min-h-[18rem]">
                            <div class="flex items-center justify-between mb-4">
                                <x-title
                                    level="h3"
                                    size="lg"
                                    weight="semibold"
                                    class="flex items-center gap-3">
                                    <i class="fas fa-list text-purple-600"></i>
                                    Item List
                                    <span class="inline-flex items-center justify-center bg-purple-100 text-purple-800 text-sm font-medium px-2 py-1 rounded">
                                        {{ count($borrowList) }}
                                    </span>
                                </x-title>
                            </div>

                            <div id="borrowListItems" class="mt-4 flex-1 space-y-3 overflow-y-auto pr-1 max-h-72 min-h-0">
                                @forelse($borrowList as $item)
                                    @php
                                        $currentQty = (int) old("items.{$item['id']}.quantity", $item['qty']);
                                        $safeMaxQty = (int) ($item['safe_max_qty'] ?? $item['total_qty']);
                                        $totalQty = (int) ($item['total_qty'] ?? $safeMaxQty);
                                    @endphp
                                    <div
                                        class="flex flex-wrap items-center gap-4 rounded-xl border border-gray-200 bg-gray-50/70 p-3"
                                        data-item-entry
                                        data-item-id="{{ $item['id'] }}"
                                        data-item-name="{{ $item['name'] }}"
                                        data-item-total="{{ $totalQty }}"
                                        data-safe-max="{{ $safeMaxQty }}"
                                        data-item-quantity="{{ $currentQty }}">
                                        <div class="flex min-w-0 flex-1 flex-col items-center gap-2 text-center">
                                            @php
                                                $photoUrl = \App\Models\Item::make([
                                                    'photo' => $item['photo'] ?? null,
                                                ])->photo_url;
                                            @endphp
                                            <img
                                                src="{{ $photoUrl }}"
                                                class="h-12 w-12 rounded object-cover"
                                                alt="{{ $item['name'] }}">
                                            <p class="text-sm font-semibold text-gray-800 break-words" title="{{ $item['name'] }}">{{ $item['name'] }}</p>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-2 rounded-full border border-gray-300 bg-white px-2 py-1">
                                                <span class="text-xs font-semibold text-gray-500">Qty</span>
                                                <input
                                                    id="item-qty-{{ $item['id'] }}"
                                                    name="items[{{ $item['id'] }}][quantity]"
                                                    type="number"
                                                    inputmode="numeric"
                                                    min="1"
                                                    max="{{ max($safeMaxQty, 1) }}"
                                                    value="{{ $currentQty }}"
                                                    data-item-max="{{ max($safeMaxQty, 1) }}"
                                                    class="borrow-quantity-input w-16 border-0 bg-transparent text-center text-sm font-semibold text-gray-800 focus:border-0 focus:ring-0"
                                                    aria-label="Quantity for {{ $item['name'] }}" />
                                            </div>

                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100"
                                                form="remove-item-{{ $item['id'] }}">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <p class="py-6 text-center text-sm text-gray-500">No items selected.</p>
                                @endforelse
                            </div>

                            @if(count($borrowList) === 0)
                                <div class="mt-4 flex justify-center">
                                    <a href="{{ route('borrow.items') }}" class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-purple-700">
                                        <i class="fas fa-plus-circle"></i> Add Items
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-lg space-y-5 h-full flex flex-col">
                            <div class="flex items-center justify-between">
                                <x-title
                                    level="h3"
                                    size="lg"
                                    weight="semibold"
                                    class="flex items-center gap-3">
                                    <i class="fas fa-clipboard-list text-purple-600"></i>
                                    Request Details
                                </x-title>
                            </div>

                            <div class="space-y-4 flex-1">
                                <div>
                                    <x-input-label for="purpose_office" value="Request Office/Agency" />
                                    <x-text-input
                                        id="purpose_office"
                                        name="purpose_office"
                                        type="text"
                                        maxlength="255"
                                        value="{{ old('purpose_office', optional($borrowRequest ?? null)->purpose_office ?? '') }}"
                                        class="mt-1 w-full rounded-lg border border-gray-400 px-3 py-2 text-sm text-gray-800 bg-white focus:border-purple-500 focus:ring-purple-500"
                                        placeholder="eg. Engineering Office – Maintenance Team" />
                                    <x-input-error :messages="$errors->get('purpose_office')" class="mt-1" />
                                </div>

                                <div class="flex-1">
                                    <x-input-label for="purpose" value="Purpose" />
                                    <textarea
                                        id="purpose"
                                        name="purpose"
                                        rows="4"
                                        maxlength="500"
                                        class="mt-1 w-full rounded-lg border border-gray-600 px-3 py-2 text-sm text-gray-800 focus:border-purple-500 focus:ring-purple-500"
                                        placeholder="Briefly describe how the items will be used">{{ old('purpose', optional($borrowRequest ?? null)->purpose ?? '') }}</textarea>
                                    <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
                                    <p class="mt-1 text-xs text-gray-500">Provide enough context for approvers to understand the request.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-lg space-y-5 h-full flex flex-col">
                            <div class="flex items-center justify-between">
                                <x-title
                                    level="h3"
                                    size="lg"
                                    weight="semibold"
                                    class="flex items-center gap-3">
                                    <i class="fas fa-map-marker-alt text-purple-600"></i>
                                    Location Details
                                </x-title>
                            </div>

                            <div class="space-y-4 flex-1">
                                <livewire:location-selector
                                    :initial-municipality-key="$oldMunicipalityKey"
                                    :initial-barangay="$oldBarangay"
                                    :initial-purok="$oldPurok"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <a href="{{ route('borrow.items') }}"
                           class="inline-flex items-center justify-center rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                        <x-button type="button" id="step1NextBtn" class="inline-flex items-center gap-2" iconName="arrow-right">
                            Next
                        </x-button>
                    </div>
                </section>

                {{-- Step 2 --}}
                <section data-step="2" class="wizard-step hidden space-y-6">
                    <div class="p-4 lg:p-6 xl:px-10">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 rounded-2xl shadow-sm border border-gray-200 bg-white p-4 lg:p-6">
                                <div class="flex items-center justify-center gap-3 mb-4">
                                    <x-secondary-button type="button" onclick="changeBorrowMonth(-1)" class="flex items-center gap-1 text-sm">
                                        <i class="fas fa-arrow-left"></i>
                                    </x-secondary-button>
                                    <span id="borrowCalendarMonth" class="text-lg font-semibold text-gray-800">-</span>
                                    <x-secondary-button type="button" onclick="changeBorrowMonth(1)" class="flex items-center gap-1 text-sm">
                                        <i class="fas fa-arrow-right"></i>
                                    </x-secondary-button>
                                </div>

                                <div class="grid grid-cols-7 gap-2 text-center text-xs sm:text-sm font-medium text-gray-600 mb-2">
                                    <span>Sun</span>
                                    <span>Mon</span>
                                    <span>Tue</span>
                                    <span>Wed</span>
                                    <span>Thu</span>
                                    <span>Fri</span>
                                    <span>Sat</span>
                                </div>

                                <div id="borrowAvailabilityCalendar" class="grid grid-cols-7 gap-2 text-sm min-h-[252px]"></div>

                                <div class="mt-5 bg-gray-50 rounded-xl p-4 border border-gray-200">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Legend</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded bg-green-100 border border-green-300"></span>
                                            Available
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded bg-blue-100 ring-2 ring-blue-400"></span>
                                            Borrow Date
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded bg-amber-100 ring-2 ring-amber-400"></span>
                                            Return Date
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded bg-gray-100 border border-gray-200"></span>
                                            Selected Range
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="h-3 w-3 rounded bg-red-100 border border-red-300"></span>
                                            Booked
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-1 rounded-2xl shadow-sm border border-gray-200 bg-white p-4 lg:p-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-clock text-purple-600"></i> Item Usage</h3>

                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="borrow_date_display_input" value="Borrow Date" />
                                        <x-text-input
                                            id="borrow_date_display_input"
                                            type="text"
                                            class="mt-1 w-full border border-gray-300 bg-gray-50 text-gray-800"
                                            readonly
                                            value="{{ $usageBorrowDisplayDefault }}"
                                        />
                                        <div class="mt-3">
                                            <select id="usage_start" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-800">
                                                <option value="" @selected(!$usageStart)>
                                                    -- Estimate Start Time --
                                                </option>
                                                @foreach($usageOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($value === $usageStart)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <x-input-label for="return_date_display_input" value="Return Date" />
                                        <x-text-input
                                            id="return_date_display_input"
                                            type="text"
                                            class="mt-1 w-full border border-gray-300 bg-gray-50 text-gray-800"
                                            readonly
                                            value="{{ $usageReturnDisplayDefault }}"
                                        />
                                        <div class="mt-3">
                                            <select id="usage_end" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-800">
                                                <option value="" @selected(!$usageEnd)>
                                                    -- Estimate End Time --
                                                </option>
                                                @foreach($usageOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($value === $usageEnd)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <input id="time_of_usage" name="time_of_usage" type="hidden" value="{{ $defaultUsageRange }}" />
                                    </div>

                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <p class="text-xs text-gray-500">Current Selection:</p>
                                        <p id="usageCurrentDisplay" class="text-sm font-semibold text-gray-900 mt-0.5">Time of Usage: {{ $usageCurrentLabel }}</p>
                                        <p id="currentSelectionDates" class="text-sm text-gray-700"></p>
                                    </div>

                                    <button type="button" class="text-sm text-red-600 hover:text-red-700" onclick="clearBorrowSelection()">Clear selection</button>

                                    <input id="borrow_date" name="borrow_date" type="hidden" value="{{ old('borrow_date', '') }}" />
                                    <input id="return_date" name="return_date" type="hidden" value="{{ old('return_date', '') }}" />
                                    <p class="sr-only">
                                        <span id="borrow_date_display">-</span>
                                        <span id="return_date_display">-</span>
                                    </p>
                                </div>

                                <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <x-secondary-button type="button" id="step2BackBtn" class="inline-flex items-center gap-2">
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </x-secondary-button>
                                    <x-button type="button" id="step2NextBtn" class="inline-flex items-center gap-2" iconName="arrow-right">
                                        Next
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Step 3 --}}
                <section data-step="3" class="wizard-step hidden space-y-6">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-2xl shadow-lg space-y-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-file-upload text-purple-600 text-xl"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">Upload Signed Support Letter</h3>
                                        <p class="text-sm text-gray-600">Please upload the scanned copy of your signed letter for transparency.</p>
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="support_letter" value="Signed Letter" />
                                    <input
                                        id="support_letter"
                                        name="support_letter"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        required
                                        data-filepond="true"
                                        data-preview-height="120"
                                        data-thumb-width="160" />
                                    <p class="text-xs text-gray-500">Accepted formats: JPG, PNG, WEBP, or PDF. Max 5MB.</p>
                                    <x-input-error :messages="$errors->get('support_letter')" class="mt-1" />
                                    <p id="letterFileName" class="text-sm text-gray-600 hidden"></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-lg space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-clipboard-list text-purple-600"></i> Borrow Summary
                            </h3>

                            <div class="space-y-3 text-sm text-gray-700">
                                <p><span class="font-medium">Borrow Period:</span> <span id="summaryBorrowDates">&mdash;</span></p>
                                <p><span class="font-medium">Time of Usage:</span> <span id="summaryUsage">--</span></p>
                                <p><span class="font-medium">Selected Address:</span> <span id="summaryAddress">&mdash;</span></p>
                                <p><span class="font-medium">Purpose &amp; Office:</span> <span id="summaryPurposeOffice">--</span></p>
                                <p><span class="font-medium">Purpose:</span> <span id="summaryPurpose">--</span></p>
                                {{-- Removed manpower summary row (deprecated) --}}
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Items</h4>
                                <ul id="summaryItemsList" class="list-disc pl-5 text-sm text-gray-700 space-y-1"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <x-secondary-button type="button" id="step3BackBtn" class="inline-flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </x-secondary-button>
                        <x-button type="button" id="openConfirmModalBtn" class="inline-flex items-center gap-2" iconName="paper-airplane">
                            Submit Borrow Request
                        </x-button>
                    </div>
                </section>
            </div>
        </form>

        @foreach($borrowList as $item)
            <form id="remove-item-{{ $item['id'] }}" action="{{ route('borrowList.remove', $item['id']) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>

    <x-modal name="borrowConfirmModal" maxWidth="3xl" background="transparent">
        <div class="w-full max-h-[85vh] bg-[#4C1D95] dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-file-circle-check"></i>
                        <span>Confirm Borrow Request</span>
                    </h3>
                    <p class="text-sm text-purple-100 mt-1">Review schedule, letter, and items before submitting.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition"
                    @click="$dispatch('close-modal', 'borrowConfirmModal')"
                >
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-white dark:bg-gray-900 px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div class="grid md:grid-cols-2 gap-5">
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Borrow Period</h4>
                            <div class="grid sm:grid-cols-2 gap-2">
                                <p><span class="font-medium">Borrow Date:</span> <span id="modalBorrowDate">-</span></p>
                                <p><span class="font-medium">Return Date:</span> <span id="modalReturnDate">-</span></p>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Time of Usage</h4>
                            <p id="modalUsage" class="text-gray-700">--</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Request Office/Agency</h4>
                            <p id="modalPurposeOffice" class="text-gray-700">--</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Purpose</h4>
                            <p id="modalPurpose" class="text-gray-700">--</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Selected Address</h4>
                            <p id="modalAddress" class="text-gray-700">-</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-800">Uploaded Letter</h4>
                        <div id="modalLetterPreviewWrapper" class="rounded-lg border border-gray-200 bg-gray-50 p-3 flex items-center justify-center min-h-[160px]">
                            <img id="modalLetterImage" alt="Uploaded letter preview" class="max-h-64 w-auto rounded-lg shadow hidden" />
                            <p id="modalLetterName" class="text-gray-700">-</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Items</h4>
                    <ul id="modalItemsList" class="list-disc pl-5 space-y-1"></ul>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex flex-wrap justify-end gap-3">
                <x-button type="button" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'borrowConfirmModal')">
                    Cancel
                </x-button>
                <x-button type="button" id="confirmBorrowRequestBtn" variant="primary" class="px-4 py-2 text-sm">
                    Submit Request
                </x-button>
            </div>
        </div>
    </x-modal>

    @vite(['resources/js/app.js'])
</x-app-layout>
