{{-- resources/views/user/borrow-items/index.blade.php --}}
<x-app-layout>
    <div class="p-6">

        {{-- Alerts --}}
        @if(session('success'))
            <x-alert type="success" :message="session('success')" />
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof window.showToast === 'function') {
                        window.showToast('success', @json(session('success')));
                    }
                });
            </script>
        @endif
        @if(session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif
        @if($errors->any())
            <x-alert type="error" />
        @endif

        {{-- Search & Borrow List (side-by-side on md+) --}}
        <div class="w-full mb-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-3">
                <!-- Search (left) -->
                <form method="GET" action="{{ route('borrow.items') }}"
                      class="flex w-full md:flex-1 md:max-w-3xl">
                    <label for="search" class="sr-only">Search items</label>
                    <input id="search"
                           type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search items..."
                           class="border border-gray-300 rounded-l-lg px-4 py-2 flex-1 focus:outline-none focus:ring-2 focus:ring-purple-300" />
                    <button type="submit"
                            class="bg-purple-600 text-white px-4 rounded-r-lg hover:bg-purple-700 focus:ring-2 focus:ring-purple-300">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <!-- Borrow List (right) -->
                <div class="flex-shrink-0 w-full md:w-auto">
                    {{-- modern purple button + blue circular badge for count --}}
                    <a href="{{ route('borrowList.index') }}"
                        class="inline-flex items-center justify-center whitespace-nowrap px-4 py-2 w-full md:w-auto bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                            <i class="fas fa-list-ul mr-2"></i>
                            <span>Borrow List</span>

                            <span id="borrowListCount"
                                class="ml-3 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-sm">
                                {{ $borrowListCount }}
                            </span>
                        </a>
                </div>
            </div>
        </div>

        {{-- Header + Borrow List button --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 mt-4">
            <x-title level="h2"
                     size="2xl"
                     weight="bold"
                     icon="archive-box-arrow-down"
                     variant="s"
                     iconStyle="plain"
                     iconColor="gov-accent">
                Borrow Items
            </x-title>
        </div>

        {{-- Items Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($items as $item)
                <div
                    class="borrow-item-card bg-white rounded-lg shadow p-4 flex flex-col"
                    data-name="{{ $item->name }}"
                    data-category="{{ $item->category }}"
                >

                    <!-- Item Image -->
                    @php
                        $photoUrl = null;
                        if ($item->photo) {
                            // Check if photo is in storage (public disk)
                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($item->photo)) {
                                $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($item->photo);
                            } 
                            // Check if it's a full HTTP URL
                            elseif (str_starts_with($item->photo, 'http')) {
                                $photoUrl = $item->photo;
                            } 
                            // Check if it's in public directory (default photo or legacy path)
                            elseif (file_exists(public_path($item->photo))) {
                                $photoUrl = asset($item->photo);
                            }
                        }
                        // Use default photo if no photo found or photo column is empty
                        if (!$photoUrl) {
                            $photoUrl = asset($defaultPhoto);
                        }
                    @endphp
                    <img src="{{ $photoUrl }}"
                         alt="{{ $item->name }}" 
                         class="h-32 w-full object-cover rounded border-2 border-purple-500 mb-3">

                    <!-- Name + Category -->
                    <h3 class="text-lg font-semibold text-gray-900">{{ $item->name }}</h3>
                    <p class="text-sm text-gray-600 mb-1">Category: {{ ucfirst($item->category) }}</p>

                    <!-- Quantities -->
                    @php
                        $isAvailable = ($item->available_qty ?? 0) > 0;
                        $iconClass = 'w-4 h-4 ' . ($isAvailable ? 'text-green-600' : 'text-red-600');
                        $availTextClass = $isAvailable ? 'text-green-700' : 'text-red-700';
                    @endphp

                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="flex items-center gap-1 text-gray-700">
                            <x-heroicon-o-archive-box class="w-4 h-4 text-purple-600" />
                            Total: <span class="font-medium">{{ $item->total_qty }}</span>
                        </span>

                        <span class="flex items-center gap-1" title="Currently available">
                            <x-heroicon-o-check-circle class="{{ $iconClass }}" />
                            Avail:
                            <span class="font-medium {{ $availTextClass }}">
                                {{ $item->available_qty }}
                            </span>
                        </span>
                    </div>

                    <!-- Borrow Form -->
                    <form action="{{ route('borrowList.add', $item->id) }}"
                        method="POST"
                        class="mt-auto borrow-add-form"
                        data-item-id="{{ $item->id }}"
                        data-item-name="{{ $item->name }}"
                        data-item-total="{{ $item->total_qty }}">
                        @csrf

                        {{-- quantity control (no inline JS, uses data attributes + classes) --}}
                        <div class="flex items-center space-x-2 mb-2 qty-control" data-item-max="{{ $item->total_qty }}">
                            <x-secondary-button type="button" class="btn-step-down">-</x-secondary-button>

                            <x-text-input 
                                type="number" 
                                name="qty" 
                                value="1" 
                                min="1" 
                                max="{{ $item->total_qty }}" 
                                class="w-16 text-center qty-input" />

                            <x-secondary-button 
                                type="button" 
                                class="btn-step-up"
                                data-max="{{ $item->total_qty }}">+</x-secondary-button>
                        </div>

                        <x-primary-button class="w-full">
                            <i class="fas fa-plus-circle mr-1"></i> Add to List
                        </x-primary-button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const QTY_ERROR_CLASSES = ['ring-2', 'ring-red-300', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-300'];
            const QTY_SUCCESS_CLASSES = ['ring-2', 'ring-green-300', 'border-green-500', 'focus:border-green-500', 'focus:ring-green-300'];

            function clearInlineError(form) {
                const existing = form.querySelector('.inline-availability-error');
                if (existing) existing.remove();
            }

            function showInlineError(form, message) {
                clearInlineError(form);
                const div = document.createElement('div');
                div.className = 'inline-availability-error mt-2 text-sm text-red-600';
                div.textContent = message;
                form.appendChild(div);
                setTimeout(() => {
                    if (div.parentNode) div.remove();
                }, 5000);
            }

            function setQuantityState(form, state, message) {
                const input = form.querySelector('input[name="qty"], .qty-input');
                if (!input) return;

                input.classList.remove(...QTY_ERROR_CLASSES, ...QTY_SUCCESS_CLASSES);

                if (state === 'error') {
                    input.classList.add(...QTY_ERROR_CLASSES);
                    if (message) {
                        showInlineError(form, message);
                    }
                    return;
                }

                if (state === 'valid') {
                    input.classList.add(...QTY_SUCCESS_CLASSES);
                }

                clearInlineError(form);
            }

            function getItemIdFromForm(form) {
                if (form.dataset && form.dataset.itemId) return form.dataset.itemId;
                try {
                    const match = form.getAttribute('action').match(/\/(\d+)(?:\/?$|\?)/);
                    if (match && match[1]) return match[1];
                } catch (error) {
                    console.warn('Failed to parse item id from action', error);
                }
                return null;
            }

            function getItemName(form) {
                return form.dataset?.itemName || 'item';
            }

            function pluralizeItem(name, count) {
                if (count === 1) return name;
                if (!name) return 'items';
                return name.endsWith('s') ? name : name + 's';
            }

            function buildAvailabilitySignature(itemId, borrowDate, returnDate, qty) {
                return [itemId, borrowDate, returnDate, qty].join('|');
            }

            async function fetchAvailability(form, qty, { force = false, signal } = {}) {
                const itemId = getItemIdFromForm(form);
                const borrowDateEl = document.getElementById('borrow_date') || document.querySelector('input[name="borrow_date"]');
                const returnDateEl = document.getElementById('return_date') || document.querySelector('input[name="return_date"]');

                const borrowDate = borrowDateEl?.value || '';
                const returnDate = returnDateEl?.value || '';

                if (!itemId || !borrowDate || !returnDate) {
                    form.dataset.availabilitySignature = '';
                    form.dataset.availabilityAvailable = '';
                    form.dataset.availabilityRemaining = '';
                    form.dataset.availabilityMessage = '';
                    return null;
                }

                const signature = buildAvailabilitySignature(itemId, borrowDate, returnDate, qty);

                if (!force && form.dataset.availabilitySignature === signature && typeof form.dataset.availabilityAvailable !== 'undefined') {
                    return {
                        signature,
                        available: form.dataset.availabilityAvailable === '1',
                        remaining: parseInt(form.dataset.availabilityRemaining || '0', 10),
                        message: form.dataset.availabilityMessage || '',
                    };
                }

                const params = new URLSearchParams({
                    borrow_date: borrowDate,
                    return_date: returnDate,
                    qty: String(Math.max(qty, 0)),
                });

                const response = await fetch(/user/availability/?, {
                    headers: { Accept: 'application/json' },
                    signal,
                });

                const json = await response.json().catch(() => null);

                if (!response.ok) {
                    const message = (json && json.message) ? json.message : 'Unable to check availability right now.';
                    throw new Error(message);
                }

                let available;
                let remaining = 0;
                let message = '';

                if (json && typeof json.available !== 'undefined') {
                    available = Boolean(json.available);
                    remaining = Number.isFinite(json.remaining) ? json.remaining : 0;
                    message = json.message || '';
                } else if (Array.isArray(json) && json.length) {
                    available = false;
                    message = 'This item is blocked for part of the selected range.';
                } else {
                    available = true;
                    remaining = qty;
                }

                form.dataset.availabilitySignature = signature;
                form.dataset.availabilityAvailable = available ? '1' : '0';
                form.dataset.availabilityRemaining = String(remaining);
                form.dataset.availabilityMessage = message;

                return { signature, available, remaining, message };
            }

            function scheduleAvailabilityCheck(form) {
                const qtyInput = form.querySelector('input[name="qty"], .qty-input');
                if (!qtyInput) return;

                if (form.__availabilityTimer) {
                    clearTimeout(form.__availabilityTimer);
                }
                if (form.__availabilityAbort) {
                    form.__availabilityAbort.abort();
                    form.__availabilityAbort = null;
                }

                form.__availabilityTimer = setTimeout(async () => {
                    const qty = Math.max(0, parseInt(qtyInput.value || '0', 10));
                    if (!qty) {
                        setQuantityState(form, 'idle');
                        form.dataset.availabilitySignature = '';
                        return;
                    }

                    const controller = new AbortController();
                    form.__availabilityAbort = controller;

                    try {
                        const result = await fetchAvailability(form, qty, { force: true, signal: controller.signal });
                        if (!result) {
                            setQuantityState(form, 'idle');
                            return;
                        }
                        if (result.available) {
                            setQuantityState(form, 'valid');
                        } else {
                            const remaining = Math.max(0, result.remaining ?? 0);
                            const itemName = getItemName(form);
                            const message = remaining > 0
                                ? 'You can only borrow ' + remaining + ' more ' + pluralizeItem(itemName, remaining) + ' in this date range.'
                                : 'Not enough ' + itemName + ' available in this date range.';
                            setQuantityState(form, 'error', message);
                        }
                    } catch (error) {
                        if (controller.signal.aborted) return;
                        console.error('Availability check failed', error);
                        setQuantityState(form, 'error', error.message || 'Unable to check availability right now.');
                    } finally {
                        form.__availabilityAbort = null;
                    }
                }, 300);
            }

            document.querySelectorAll('form.borrow-add-form').forEach((form) => {
                const qtyInput = form.querySelector('input[name="qty"], .qty-input');
                if (qtyInput) {
                    qtyInput.addEventListener('input', () => {
                        setQuantityState(form, 'idle');
                        scheduleAvailabilityCheck(form);
                    });
                }

                const borrowDateEl = document.getElementById('borrow_date') || document.querySelector('input[name="borrow_date"]');
                const returnDateEl = document.getElementById('return_date') || document.querySelector('input[name="return_date"]');

                [borrowDateEl, returnDateEl].forEach((field) => {
                    if (!field) return;
                    field.addEventListener('change', () => {
                        setQuantityState(form, 'idle');
                        scheduleAvailabilityCheck(form);
                    });
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"], .x-primary-button') || form.querySelector('button');
                    if (submitBtn) submitBtn.disabled = true;

                    try {
                        const qtyField = form.querySelector('input[name="qty"], .qty-input');
                        const qty = Math.max(0, parseInt(qtyField?.value || '0', 10));
                        const maxAttr = qtyField?.getAttribute('max');
                        const datasetMax = form.dataset.itemTotal ? parseInt(form.dataset.itemTotal, 10) : null;
                        const maxAllowed = maxAttr ? parseInt(maxAttr, 10) : datasetMax;
                        const itemName = getItemName(form);

                        if (!qty || qty <= 0) {
                            setQuantityState(form, 'error', 'Please enter a valid quantity.');
                            return;
                        }
                        if (Number.isFinite(maxAllowed) && qty > maxAllowed) {
                            const message = maxAllowed <= 0
                                ? 'Not enough ' + itemName + ' available right now.'
                                : 'Only ' + maxAllowed + ' ' + pluralizeItem(itemName, maxAllowed) + ' are available.';
                            setQuantityState(form, 'error', message);
                            return;
                        }

                        const availability = await fetchAvailability(form, qty, { force: true });
                        if (availability && !availability.available) {
                            const remaining = Math.max(0, availability.remaining ?? 0);
                            const message = remaining > 0
                                ? 'You can only borrow ' + remaining + ' more ' + pluralizeItem(itemName, remaining) + ' in this date range.'
                                : 'Not enough ' + itemName + ' available in this date range.';
                            setQuantityState(form, 'error', message);
                            return;
                        }

                        setQuantityState(form, availability ? 'valid' : 'idle');
                        form.submit();
                    } catch (error) {
                        console.error('Availability validation failed', error);
                        setQuantityState(form, 'error', error.message || 'Unable to check availability right now.');
                    } finally {
                        if (submitBtn) submitBtn.disabled = false;
                    }
                });

                scheduleAvailabilityCheck(form);
            });
        });
    </script>

</x-app-layout>





