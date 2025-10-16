{{-- resources/views/user/borrow-items/borrowList.blade.php --}}
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

            <div class="p-6 max-w-7xl mx-auto">
                {{-- Alerts --}}
                @if(session('success'))
                    <x-alert type="success" :message="session('success')" />
                @endif
                @if(session('error'))
                    <x-alert type="error" :message="session('error')" />
                @endif
                @if($errors->any())
                    <x-alert type="error" />
                @endif

                <div class="flex items-start gap-6">
                    {{-- Left column: Borrow List --}}
                <aside class="w-full lg:w-1/3">
                <div class="space-y-6">
                    <!-- Item List Card (larger padding, softer corners, stronger shadow) -->
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

                        <div id="borrowListItems" class="space-y-3 overflow-auto max-h-[40vh]">
                            @forelse($borrowList as $item)
                                <div class="flex items-center justify-between border-b pb-2">
                                    <div class="flex items-center space-x-3">
                                        <img
                                            src="{{ $item['photo'] ? asset($item['photo']) : asset($defaultPhotos[$item['category']] ?? 'images/no-image.png') }}"
                                            class="h-12 w-12 object-cover rounded"
                                            alt="{{ $item['name'] }}">
                                        <div>
                                            <p class="font-medium">{{ $item['name'] }}</p>
                                            <p class="text-sm text-gray-600">Quantity: {{ $item['qty'] }}</p>
                                        </div>
                                    </div>

                                    <form action="{{ route('borrowList.remove', $item['id']) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button>
                                            <i class="fas fa-trash"></i>
                                        </x-danger-button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-gray-500">No items selected.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Logistics & Roles Card (separate small box under Item List) -->
                    <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <x-title 
                                level="h3"
                                size="lg"
                                weight="semibold"
                                icon="user-plus"
                                variant="s"
                                iconStyle="circle"
                                iconBg="gov-accent"
                                iconColor="white"
                                class="flex items-center gap-3 text-center mx-auto">
                                Resource Allocation
                            </x-title>
                        </div>

                        <!-- Number of Manpower -->
                        <div class="mb-5">
                            <x-input-label for="manpower_count" value="Number of Manpower (Optional)" />
                            <x-text-input class="border border-gray-600"  id="manpower_count" type="number" name="manpower_count" min="0" class="w-full mt-1"
                                        value="{{ old('manpower_count', '') }}" style="border: 1px solid black; background-color: white; color: black;"/>
                            <x-input-error :messages="$errors->get('manpower_count')" class="mt-1" />
                            <p id="manpower_hint" class="text-xs text-gray-400 mt-2">Add manpower if you need personnel to handle items.</p>
                        </div>

                        <!-- Delivery Location -->
                        <div class="mb-5">
                            <x-input-label for="location" value="Location" />
                            <x-text-input id="location" type="text" name="location" class="w-full mt-1"
                                    value="{{ old('location', optional($borrowRequest ?? null)->location ?? '') }}" 
                                    placeholder="Enter delivery address or location" style="border: 1px solid black; background-color: white; color: black;"/>
                            <x-input-error :messages="$errors->get('location')" class="mt-1" />
                        </div>

                        <!-- Assign Roles -->
                        <div id="manpowerRolesWrapper" class="mb-1 hidden">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm text-gray-700 font-medium">Assign Roles</div>
                                <button type="button" id="addRoleBtn" class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-600 text-white text-sm hover:bg-purple-700">
                                    <i class="fas fa-plus mr-2"></i> Add Role
                                </button>
                            </div>

                            <div id="manpowerRolesContainer" class="space-y-3 max-h-44 overflow-auto border rounded p-3 bg-white pr-4">
                                <!-- JS will populate role rows here. -->
                            </div>

                            <div id="manpowerRolesWarning" class="text-sm text-red-600 mt-2 hidden"></div>
                            <div class="text-sm text-gray-500 mt-3">Total selected manpower: <span id="manpowerRolesTotal">0</span> / <span id="manpowerRequestedDisplay">0</span></div>
                        </div>

                        <!-- Keep role row template unchanged inside aside -->
                        <template id="manpowerRoleRowTemplate">
                            <div class="grid grid-cols-12 gap-3 items-center border-b pb-3 pt-2 role-row">
                                <!-- Role (wider) -->
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

                                <!-- Quantity (smaller) -->
                                <div class="col-span-4">
                                    <label class="text-xs text-gray-600">Quantity</label>
                                    <input type="number" min="0" class="w-full border rounded px-2 py-1 role-qty-input text-center" value="0" />
                                </div>

                                <!-- Notes (bigger) -->
                                <!-- <div class="col-span-2">
                                    <label class="text-xs text-gray-600">Notes</label>
                                    <input type="text" class="w-full border rounded px-2 py-1 role-notes-input" placeholder="Notes (optional)" />
                                </div> -->

                                <!-- Remove button (aligned right) -->
                                <div class="col-span-2 flex items-end= justify-end">
                                    <button type="button" class="remove-role-btn inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-700 text-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>
            </aside>


                {{-- Right column: Calendar & form --}}
                <main class="w-full lg:flex-1">
                    <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col h-full">
                        <x-title  
                            level="h2"
                            size="2xl"
                            weight="bold"
                            icon="calendar-date-range"
                            variant="s"
                            iconStyle="circle"
                            iconBg="gov-accent" 
                            iconColor="white"
                            class="text-center mx-auto">
                            Checkout Dates
                        </x-title>

                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Borrow Date:</span>
                                    <input id="borrow_date_display" type="text" readonly
                                        value="{{ old('borrow_date') ? \Carbon\Carbon::parse(old('borrow_date'))->format('F j, Y') : '' }}"
                                        placeholder="—"
                                        class="inline-block ml-2 border-0 bg-transparent text-sm" />
                                </p>
                                <p class="text-sm text-gray-700 mt-1">
                                    <span class="font-medium">Return Date:</span>
                                    <input id="return_date_display" type="text" readonly
                                        value="{{ old('return_date') ? \Carbon\Carbon::parse(old('return_date'))->format('F j, Y') : '' }}"
                                        placeholder="—"
                                        class="inline-block ml-2 border-0 bg-transparent text-sm" />
                                </p>
                            </div>

                            {{-- Clear button (top-right) --}}
                            <div class="ml-4">
                                <x-danger-button type="button" onclick="clearBorrowSelection()">
                                    Clear
                                </x-danger-button>
                            </div>
                        </div>

                        <form id="borrowListForm" action="{{ route('borrowList.submit') }}" method="POST" class="flex-1 flex flex-col justify-between">
                        @csrf

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
        
        
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const submitBtn = document.getElementById('floatingSubmitBtn');
        const form = document.getElementById('borrowListForm');

        if (!submitBtn || !form) return;

        submitBtn.addEventListener('click', function (ev) {
            ev.preventDefault();
            // Basic client-side validation check: ensure borrow list not empty (optional)
            // You can remove this check if server-side validation is enough.
            const borrowItems = document.querySelectorAll('#borrowListItems > div');
            // If you want to enforce non-empty, uncomment below:
            // if (!borrowItems.length) { alert('Your Borrow List is empty.'); return; }

            // disable button briefly to avoid double-submit
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

            // submit the main form
            form.submit();

            // re-enable after a short time (in case submission fails)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }, 3000);
        });
    });
    </script>

</x-app-layout>
