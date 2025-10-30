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
                                        icon="list-bullet"
                                        variant="s"
                                        iconStyle="circle"
                                        iconBg="gov-accent"
                                        iconColor="white"
                                        class="flex items-center gap-3">
                                        Item List
                                        <span class="inline-flex items-center justify-center bg-purple-100 text-purple-800 text-sm font-medium px-2 py-1 rounded">
                                            {{ count($borrowList) }}
                                        </span>
                                    </x-title>
                                </div>

                                <div id="borrowListItems" class="space-y-3 max-h-[40vh] overflow-auto">
                                    @forelse($borrowList as $item)
                                        <div class="flex items-center justify-between border-b pb-2" data-item-entry data-item-name="{{ $item['name'] }}" data-item-quantity="{{ $item['qty'] }}">
                                            <div class="flex items-center space-x-3">
                                                <img
                                                    src="{{ $item['photo'] ? asset($item['photo']) : asset($defaultPhotos[$item['category']] ?? 'images/no-image.png') }}"
                                                    class="h-12 w-12 object-cover rounded"
                                                    alt="{{ $item['name'] }}">
                                                <div>
                                                    <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                                                    <p class="text-sm text-gray-600">Quantity: {{ $item['qty'] }}</p>
                                                </div>
                                            </div>

                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-full bg-red-100 px-3 py-2 text-sm text-red-700 hover:bg-red-200 transition"
                                                    form="remove-item-{{ $item['id'] }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    @empty
                                        <p class="text-gray-500 text-sm text-center py-6">No items selected.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 space-y-5">
                            <div class="flex items-center justify-between">
                                <x-title
                                    level="h3"
                                    size="lg"
                                    weight="semibold"
                                    icon="user-plus"
                                    variant="s"
                                    iconStyle="circle"
                                    iconBg="gov-accent"
                                    iconColor="white">
                                    Resource Allocation
                                </x-title>
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
                                    class="w-full mt-1 border border-gray-600"
                                />
                                <p class="text-xs text-gray-400 mt-2">Add manpower if you need personnel to handle items.</p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="location_municipality" value="Municipality" />
                                    <select id="location_municipality"
                                            class="w-full mt-1 border border-gray-600 rounded-md px-3 py-2 bg-white text-gray-800"
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
                                            class="w-full mt-1 border border-gray-600 rounded-md px-3 py-2 bg-white text-gray-800"
                                            data-initial="{{ $oldBarangay ?? '' }}"
                                            disabled>
                                        <option value="">Select barangay</option>
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="location_purok" value="Purok / Zone / Sitio" />
                                    <select id="location_purok"
                                            class="w-full mt-1 border border-gray-600 rounded-md px-3 py-2 bg-white text-gray-800"
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
                                        class="w-full mt-1 border border-gray-600 bg-gray-100 text-gray-800"
                                        readonly
                                        value="{{ $oldLocation }}"
                                    />
                                </div>

                                <input type="hidden" id="location" name="location" value="{{ $oldLocation }}">
                                <x-input-error :messages="$errors->get('location')" class="mt-1" />
                            </div>

                            <div id="manpowerRolesWrapper" class="space-y-4 hidden">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-700 font-medium">Assign Roles</div>
                                    <button type="button"
                                            id="addRoleBtn"
                                            class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-600 text-white text-sm hover:bg-purple-700">
                                        <i class="fas fa-plus mr-2"></i> Add Role
                                    </button>
                                </div>

                                <div id="manpowerRolesContainer" class="space-y-3 max-h-44 overflow-auto border rounded p-3 bg-white pr-4"></div>

                                <div id="manpowerRolesWarning" class="text-sm text-red-600 hidden"></div>
                                <div class="text-sm text-gray-500">
                                    Total selected manpower:
                                    <span id="manpowerRolesTotal">0</span>
                                    /
                                    <span id="manpowerRequestedDisplay">0</span>
                                </div>
                            </div>

                            <template id="manpowerRoleRowTemplate">
                                <div class="grid grid-cols-12 gap-3 items-center border-b pb-3 pt-2 role-row">
                                    <div class="col-span-6">
                                        <label class="text-xs text-gray-600">Role</label>
                                        <select class="w-full border rounded px-2 py-1 role-select">
                                            <option value="">— Select role —</option>
                                            <option value="Setup">Setup</option>
                                            <option value="Operator">Operator</option>
                                            <option value="Driver">Driver</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <input class="hidden w-full mt-2 border rounded px-2 py-1 role-other-input" placeholder="Specify other role" />
                                    </div>
                                    <div class="col-span-4">
                                        <label class="text-xs text-gray-600">Quantity</label>
                                        <input type="number" min="0" class="w-full border rounded px-2 py-1 role-qty-input text-center" value="0" />
                                    </div>
                                    <div class="col-span-2 flex items-end justify-end">
                                        <button type="button" class="remove-role-btn inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-700 text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
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
                    <div class="bg-white p-6 rounded-2xl shadow-lg space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Borrow Date:</span>
                                    <span id="borrow_date_display" class="ml-2 text-gray-900">—</span>
                                </p>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span class="font-medium">Return Date:</span>
                                    <span id="return_date_display" class="ml-2 text-gray-900">—</span>
                                </p>
                            </div>
                            <x-danger-button type="button" onclick="clearBorrowSelection()">
                                Clear Selection
                            </x-danger-button>
                        </div>

                        <input id="borrow_date" name="borrow_date" type="hidden" value="{{ old('borrow_date', '') }}" />
                        <input id="return_date" name="return_date" type="hidden" value="{{ old('return_date', '') }}" />

                        <div class="border rounded-2xl p-4 bg-white shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <x-secondary-button type="button" onclick="changeBorrowMonth(-1)" class="flex items-center gap-1 text-sm">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </x-secondary-button>
                                <span id="borrowCalendarMonth" class="text-lg font-semibold text-gray-800">—</span>
                                <x-secondary-button type="button" onclick="changeBorrowMonth(1)" class="flex items-center gap-1 text-sm">
                                    Next <i class="fas fa-arrow-right"></i>
                                </x-secondary-button>
                            </div>

                            <div class="grid grid-cols-7 text-center text-xs font-semibold text-gray-500 mb-2">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>

                            <div id="borrowAvailabilityCalendar" class="grid grid-cols-7 gap-2 text-sm min-h-[260px]"></div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <span class="flex items-center gap-2">
                                <span class="h-4 w-4 rounded border border-green-500 bg-green-100"></span> Available
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-4 w-4 rounded border border-red-500 bg-red-200"></span> Booked
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-4 w-4 rounded border border-blue-500 bg-blue-100"></span> Borrow Date
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-4 w-4 rounded border border-orange-500 bg-orange-100"></span> Return Date
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-4 w-4 rounded border border-gray-400 bg-gray-100"></span> Selected Range
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <x-secondary-button type="button" id="step2BackBtn" class="inline-flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </x-secondary-button>
                        <x-button type="button" id="step2NextBtn" class="inline-flex items-center gap-2" iconName="arrow-right">
                            Next
                        </x-button>
                    </div>
                </section>

                {{-- Step 3 --}}
                <section data-step="3" class="wizard-step hidden space-y-6">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="bg-white p-6 rounded-2xl shadow-lg space-y-4">
                            <x-input-label for="support_letter" value="Photo Upload for Letter" />
                            <x-text-input id="support_letter"
                                          type="file"
                                          name="support_letter"
                                          accept="image/*,.pdf"
                                          class="w-full mt-2 border border-gray-600"
                                          required />
                            <p class="text-xs text-gray-500">Accepted formats: JPG, PNG, WEBP, or PDF. Max 5MB.</p>
                            <x-input-error :messages="$errors->get('support_letter')" class="mt-1" />
                            <p id="letterFileName" class="text-sm text-gray-600 hidden"></p>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-lg space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-clipboard-list text-purple-600"></i> Borrow Summary
                            </h3>

                            <div class="space-y-3 text-sm text-gray-700">
                                <p><span class="font-medium">Borrow Period:</span> <span id="summaryBorrowDates">—</span></p>
                                <p><span class="font-medium">Selected Address:</span> <span id="summaryAddress">—</span></p>
                                <p><span class="font-medium">Manpower Requested:</span> <span id="summaryManpower">—</span></p>
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
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Borrow Period</h4>
                    <div class="grid sm:grid-cols-2 gap-2">
                        <p><span class="font-medium">Borrow Date:</span> <span id="modalBorrowDate">—</span></p>
                        <p><span class="font-medium">Return Date:</span> <span id="modalReturnDate">—</span></p>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Items</h4>
                    <ul id="modalItemsList" class="list-disc pl-5 space-y-1"></ul>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Selected Address</h4>
                    <p id="modalAddress" class="text-gray-700">—</p>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Uploaded Letter</h4>
                    <p id="modalLetterName" class="text-gray-700">—</p>
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
