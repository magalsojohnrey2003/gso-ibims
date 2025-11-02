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
    // Default to 09:00-17:00 as requested
    $defaultUsageRange = old('time_of_usage', optional($borrowRequest ?? null)->time_of_usage ?? '09:00-17:00');
    [$usageStart, $usageEnd] = array_pad(explode('-', $defaultUsageRange), 2, null);
    $firstUsageKey = $usageKeys[0] ?? '06:00';
    $lastUsageKey = $usageKeys[count($usageKeys) - 1] ?? '22:00';

    if (! in_array($usageStart, $usageKeys, true)) {
        $usageStart = '09:00';
    }
    if (! in_array($usageEnd, $usageKeys, true)) {
        $usageEnd = '17:00';
    }

    $startIndex = array_search($usageStart, $usageKeys, true) ?: 0;
    $endIndex = array_search($usageEnd, $usageKeys, true);
    if ($endIndex === false || $endIndex <= $startIndex) {
        $endIndex = min($startIndex + 2, count($usageKeys) - 1);
        $usageEnd = $usageKeys[$endIndex];
    }

    $defaultUsageRange = "{$usageStart}-{$usageEnd}";
    $usageCurrentLabel = "{$usageOptions[$usageStart]} - {$usageOptions[$usageEnd]}";

    $oldBorrowDateValue = old('borrow_date', optional($borrowRequest ?? null)->borrow_date ?? null);
    $oldReturnDateValue = old('return_date', optional($borrowRequest ?? null)->return_date ?? null);

    $usageBorrowDisplayDefault = $oldBorrowDateValue
        ? \Illuminate\Support\Carbon::parse($oldBorrowDateValue)->format('F j, Y')
        : 'Select on calendar';
    $usageReturnDisplayDefault = $oldReturnDateValue
        ? \Illuminate\Support\Carbon::parse($oldReturnDateValue)->format('F j, Y')
        : 'Select on calendar';
@endphp

<x-app-layout>
    <x-title
        level="h2"
        size="2xl"
        weight="bold"
        icon="shopping-cart"
        variant="s"
        iconStyle="circle"
        iconBg="gov-accent"
        iconColor="white">
        Borrow List
    </x-title>

    <div class="p-6 max-w-7xl mx-auto space-y-6">
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
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-2xl shadow-lg">
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

                                <div id="borrowListItems" class="space-y-3 max-h-[40vh] overflow-auto">
                                    @forelse($borrowList as $item)
                                        @php
                                            $currentQty = (int) old("items.{$item['id']}.quantity", $item['qty']);
                                        @endphp
                                        <div
                                            class="flex flex-col gap-4 rounded-xl border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                                            data-item-entry
                                            data-item-id="{{ $item['id'] }}"
                                            data-item-name="{{ $item['name'] }}"
                                            data-item-total="{{ $item['total_qty'] }}"
                                            data-item-quantity="{{ $currentQty }}">
                                            <div class="flex items-center gap-3">
                                                <img
                                                    src="{{ $item['photo'] ? asset('storage/'.$item['photo']) : asset($defaultPhotos[$item['category']] ?? 'images/no-image.png') }}"
                                                    class="h-14 w-14 rounded object-cover"
                                                    alt="{{ $item['name'] }}">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-800">{{ $item['name'] }}</p>
                                                    <p class="text-sm text-gray-500">Available: {{ $item['total_qty'] }} total</p>
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:gap-4">
                                                <div class="flex items-center gap-2">
                                                    <label for="item-qty-{{ $item['id'] }}" class="text-sm font-medium text-gray-700">Qty</label>
                                                    <input
                                                        id="item-qty-{{ $item['id'] }}"
                                                        name="items[{{ $item['id'] }}][quantity]"
                                                        type="number"
                                                        inputmode="numeric"
                                                        min="1"
                                                        max="{{ $item['total_qty'] }}"
                                                        value="{{ $currentQty }}"
                                                        data-item-max="{{ $item['total_qty'] }}"
                                                        class="borrow-quantity-input w-20 rounded-lg border border-gray-300 px-3 py-1 text-center text-sm font-semibold text-gray-800 focus:border-purple-500 focus:ring-purple-500" />
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center justify-center rounded-full bg-red-100 px-3 py-2 text-sm text-red-700 transition hover:bg-red-200"
                                                    form="remove-item-{{ $item['id'] }}">
                                                    <i class="fas fa-trash"></i>
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
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-2xl shadow-lg space-y-5">
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

                                <div class="space-y-4">
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

                                    <div>
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

                                    <div>
                                        <x-input-label for="manpower_count" value="Number of Manpower (Optional)" />
                                        <x-text-input
                                            id="manpower_count"
                                            name="manpower_count"
                                            type="number"
                                            min="1"
                                            max="99"
                                            value="{{ old('manpower_count') }}"
                                            class="mt-1 w-full rounded-lg border border-gray-400 px-3 py-2 text-sm text-gray-800 bg-white focus:border-purple-500 focus:ring-purple-500"
                                        />
                                        <p class="mt-2 text-xs text-gray-500">Add manpower if you need personnel to handle items.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-2xl shadow-lg space-y-5">
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

                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="location_municipality" value="Municipality" />
                                        <select id="location_municipality"
                                                class="mt-1 w-full rounded-md border border-gray-600 bg-white px-3 py-2 text-gray-800"
                                                data-initial="{{ $oldMunicipalityKey ?? '' }}">
                                            <option value="" disabled selected>Select municipality</option>
                                            @foreach($municipalities as $key => $definition)
                                                <option value="{{ $key }}"
                                                        data-label="{{ $definition['label'] ?? $key }}"
                                                        @selected(($oldMunicipalityKey ?? '') === $key)>
                                                    {{ $definition['label'] ?? $key }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <x-input-label for="location_barangay" value="Barangay" />
                                        <select id="location_barangay"
                                                class="mt-1 w-full rounded-md border border-gray-600 bg-white px-3 py-2 text-gray-800"
                                                data-initial="{{ $oldBarangay ?? '' }}"
                                                disabled>
                                            <option value="">Select barangay</option>
                                        </select>
                                    </div>

                                    <div>
                                        <x-input-label for="location_purok" value="Purok / Zone / Sitio" />
                                        <select id="location_purok"
                                                class="mt-1 w-full rounded-md border border-gray-600 bg-white px-3 py-2 text-gray-800"
                                                data-initial="{{ $oldPurok ?? '' }}"
                                                disabled>
                                            <option value="">Select purok / zone / sitio</option>
                                        </select>
                                    </div>

                                    <div id="locationDisplayWrapper" class="{{ $oldLocation ? '' : 'hidden' }}">
                                        <x-input-label for="location_display" value="Selected Address" />
                                        <x-text-input
                                            id="location_display"
                                            type="text"
                                            class="mt-1 w-full border border-gray-600 bg-gray-100 text-gray-800"
                                            readonly
                                            value="{{ $oldLocation }}"
                                        />
                                    </div>

                                    <input type="hidden" id="location" name="location" value="{{ $oldLocation }}">
                                    <x-input-error :messages="$errors->get('location')" class="mt-1" />
                                </div>
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
                    <div class="max-w-7xl mx-auto p-4 lg:p-6">
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
                                    </div>

                                    <div>
                                        <x-input-label for="usage_start" value="Select Usage Hours" />
                                        <div class="mt-1 grid grid-cols-2 gap-3">
                                            <select id="usage_start" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-800">
                                                @foreach($usageOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($value === $usageStart)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <select id="usage_end" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-800">
                                                @foreach($usageOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($value === $usageEnd)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
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
                                        class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-gray-400 bg-gray-50 px-4 py-3 text-sm text-gray-700 focus:border-purple-500 focus:ring-purple-500" />
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
                                <p><span class="font-medium">Manpower Requested:</span> <span id="summaryManpower">&mdash;</span></p>
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

<<<<<<< Updated upstream
        @foreach($borrowList as $item)
            <form id="remove-item-{{ $item['id'] }}" action="{{ route('borrowList.remove', $item['id']) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
=======
                        {{-- Hidden inputs MUST be inside the form so they are POSTed --}}
                        <input id="borrow_date" name="borrow_date" type="hidden" value="{{ old('borrow_date', '') }}" />
                        <input id="return_date" name="return_date" type="hidden" value="{{ old('return_date', '') }}" />

                        {{-- Calendar card area --}}
                        <div class="border rounded p-4 bg-white flex-1">
                            {{-- Month header: title centered, prev/next controls inside the card --}}
                            <!-- Add this small instruction under the month title or near the top of the calendar card -->

                            <div class="flex items-center justify-between mb-3 relative">
                                <!-- arrows are absolutely positioned inside this relative container -->
                                <x-secondary-button type="button" onclick="changeBorrowMonth(-1)" class="text-lg" style="position:absolute; left:12px; top:50%; transform:translateY(-50%);">
                                    &lt;
                                </x-secondary-button>

                                <div class="w-full text-center">
                                    <!-- add horizontal padding so title sits nicely between arrows -->
                                    <span id="borrowCalendarMonth" class="text-lg font-semibold" style="padding:0 48px; display:inline-block;">
                                        <!-- JS will fill -->
                                    </span>
                                </div>

                                <x-secondary-button type="button" onclick="changeBorrowMonth(1)" class="text-lg" style="position:absolute; right:12px; top:50%; transform:translateY(-50%);">
                                    &gt;
                                </x-secondary-button>
                            </div>

                            {{-- Weekday header (kept from original) --}}
                            <div class="grid grid-cols-7 text-center text-xs font-semibold text-gray-600 mb-2">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>

                            {{-- The calendar grid (your JS will populate this) --}}
                            <div id="borrowAvailabilityCalendar" class="rounded p-2 min-h-[260px]">
                                {{-- JS will render month day cells here --}}
                            </div>

                            {{-- legend (removed "Today" per request) --}}
                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm">
                                <span class="flex items-center"><span class="w-4 h-4 bg-green-200 border mr-1 rounded"></span>Available</span>
                                <span class="flex items-center"><span class="w-4 h-4 bg-red-500 border mr-1 rounded"></span>Blocked</span>
                                <span class="flex items-center"><span class="w-4 h-4 bg-blue-500 border mr-1 rounded"></span>Selected (3-day)</span>
                            </div>
                        </div>

                        <!-- Step 3: Upload Letter -->
                        <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg mt-4">
                            <button
                                type="button"
                                data-step3-header
                                aria-expanded="false"
                                aria-controls="step3-letter-body"
                                class="w-full text-left focus:outline-none">
                                <div class="flex items-center justify-between p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
                                        <h4 class="text-lg font-semibold text-gray-900">Upload Letter</h4>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" data-step3-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div id="step3-letter-body" data-step3-body class="p-4 max-h-0 overflow-hidden opacity-0">
                                <div class="mb-3">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Letter</label>
                                    <input id="letter" name="letter" type="file" accept="image/*,application/pdf"
                                            data-filepond="true"
                                            data-preview-height="120"
                                            data-thumb-width="160" />
                                    <x-input-error :messages="$errors->get('letter')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                      {{-- Floating action buttons (icon-only) --}}
                        <div id="borrowFloatingActions" class="fixed right-6 bottom-6 z-50 flex flex-col gap-3 items-center">
                            <!-- Back (anchor) -->
                            <a href="{{ route('borrow.items') }}"
                            title="Back"
                            aria-label="Back"
                            class="w-14 h-14 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center shadow-md border">
                                <i class="fas fa-arrow-left"></i>
                            </a>

                            <!-- Submit (button triggers the main form) -->
                            <button id="floatingSubmitBtn"
                                    type="button"
                                    title="Submit Borrow Request"
                                    aria-label="Submit Borrow Request"
                                    class="w-14 h-14 rounded-full bg-purple-600 hover:bg-purple-700 text-white flex items-center justify-center shadow-md">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>

                    </form>
                </div>
            </main>
        </div>
        
        
>>>>>>> Stashed changes
    </div>

    <x-modal name="borrowConfirmModal" maxWidth="3xl">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-file-circle-check text-purple-600"></i>
                    <span>Confirm Borrow Request</span>
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'borrowConfirmModal')">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

                    <div class="space-y-5 text-sm text-gray-700">
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

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <x-button type="button" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'borrowConfirmModal')">
                    Cancel
                </x-button>
                <x-button type="button" id="confirmBorrowRequestBtn" variant="primary" class="px-4 py-2 text-sm">
                    Confirm
                </x-button>
            </div>
        </div>
    </x-modal>

    <script>
        window.LOCATION_ENDPOINTS = {
            barangays: "{{ route('user.locations.barangays') }}",
            puroks: "{{ route('user.locations.puroks') }}"
        };
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
