<!-- resources/views/admin/items/create.blade.php -->
<form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-property-form data-add-items-form> 
@csrf

<div data-add-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
<div data-add-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

<!-- Step 1: Basic Information -->
<div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
    <div class="flex items-center mb-4">
        <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</div>
        <h4 class="text-lg font-semibold text-gray-900">Item Name & Quantity</h4>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="name" value="Item Name" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                :value="old('name')"
                required
                autofocus
                data-add-field="name"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Category and Quantity in the Same Row -->
        <div class="grid grid-cols-2 gap-4">
            

            <div>
                <x-input-label for="quantity" value="Quantity" />
                <x-text-input
                    id="quantity"
                    name="quantity"
                    type="number"
                    min="1"
                    step="1"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                    :value="old('quantity', 1)"
                    required
                    data-add-field="quantity"
                />
                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Identification & Codes -->
<div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
    <div class="flex items-center mb-4">
        <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</div>
        <h4 class="text-lg font-semibold text-gray-900">Generate Property Numbers</h4>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <x-input-label for="year_procured" value="Year Procured" />
            <select id="year_procured" name="year_procured"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                    data-property-segment="year"
                    data-add-field="year">
                <option value="">-- Select year --</option>
                @php
                    $start = 2020;
                    $current = date('Y');
                @endphp
                @for($y = $current; $y >= $start; $y--)
                    <option value="{{ $y }}" {{ old('year_procured') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <x-input-error :messages="$errors->get('year_procured')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="category" value="Category" />
                <select id="category" name="category"
                    class="mt-1 block w-full min-w-0 appearance-none border rounded px-3 py-2"
                    data-category-select data-field="category" data-add-field="category">
                </select>
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="gla" value="GLA" />
            <x-text-input
                id="gla"
                name="gla"
                type="text"
                maxlength="4"
                inputmode="numeric"
                pattern="\d{1,4}"
                class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-2 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                :value="old('gla')"
                placeholder="digits only (1-4)"
                data-add-field="gla"
            />
            <x-input-error :messages="$errors->get('gla')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="start_serial" value="Serial" />
            <x-text-input
                id="start_serial"
                name="start_serial"
                type="text"
                maxlength="6"
                inputmode="numeric"
                class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                :value="old('start_serial')"
                data-property-segment="serial"
                data-add-field="serial"
            />
            <p class="mt-1 text-xs hidden" data-serial-feedback></p>
            <x-input-error :messages="$errors->get('start_serial')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="office_code" value="Office" />
            <select id="office_code" name="office_code"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                    data-office-select
                    data-add-field="office">
                <!-- JS will populate; initially blank & disabled if no offices -->
            </select>
            <p class="mt-1 text-xs text-red-600 hidden" data-office-error>Office code must be 1–4 alphanumeric.</p>
            <x-input-error :messages="$errors->get('office_code')" class="mt-2" />
        </div>
    </div>


    <!-- Hidden category code: populated by category selection JS -->
    <input type="hidden" name="category_code" data-property-segment="category" value="" />

    <!-- Property Number Rows -->
    <div class="bg-gray-50 shadow-md rounded-lg p-4 mt-4">
      <div class="flex items-center mb-4">
        <h4 class="text-lg font-semibold text-gray-900"> Property Number Rows</h4>
      </div>

      <div class="text-xs text-gray-600 mb-2">Rows will be generated based on Quantity, Year, Category, Serial and Office. You can edit rows before saving.</div>

      <div id="generated_rows_container"
          class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white"
          aria-live="polite">
        {{-- JS will inject per-row panels here — populate using quantity/year/category/serial/office --}}
      </div>
    </div>
</div>

<!-- Step 3: Additional Details (fixed alignment) -->
<div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg"> 
    <!-- button keeps behavior, but padding is moved to inner wrapper to match other headers -->
    <button
        type="button"
        data-step3-header
        aria-expanded="false"
        aria-controls="step3-body"
        class="w-full text-left focus:outline-none">
        <div class="flex items-center justify-between p-4"> {{-- <-- p-4 here matches the other headers --}}
            <div class="flex items-center space-x-3">
                <div class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
                <h4 class="text-lg font-semibold text-gray-900">Additional Details</h4>
            </div>

            <svg class="w-5 h-5 text-gray-500 transform transition-transform" data-step3-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </button>

    <div id="step3-body" data-step3-body class="p-4 max-h-0 overflow-hidden opacity-0">
        <div>
            <x-input-label for="description" value="Description" />
            <textarea
                id="description"
                name="description"
                rows="3"
                class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                data-add-field="description"
            >{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div><br>

        <div class="mb-3">
    <label class="block text-sm font-semibold text-gray-700 mb-2">Photo Upload</label>

    <input id="photo" name="photo" type="file" accept="image/*"
            data-filepond="true"
            data-preview-height="120"
            data-thumb-width="160" />
    </div>
    </div>
</div>
   <div class="mt-4 border-t pt-4 flex justify-end gap-3">
            <x-button
                variant="secondary"
                iconName="x-mark"
                type="button"
                data-add-cancel
                x-on:click="$dispatch('close-modal', 'create-item')">
                Cancel
            </x-button>

            <x-button
                variant="primary"
                iconName="document-check"
                type="submit"
                data-add-submit>
                Save
            </x-button>
        </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const yearInput = document.querySelector('#year_procured');
    const quantityInput = document.querySelector('#quantity');
    const glaInput = document.querySelector('#gla');
    const serialInput = document.querySelector('#start_serial');
    const officeInput = document.querySelector('#office_code');
    const categoryInputHidden = document.querySelector('input[name="category_code"], input[data-property-segment="category"]');
    const generatedRowsContainer = document.querySelector('#generated_rows_container');
    const categorySelect = document.querySelector('[data-category-select]');
    const ev = new Event('input', { bubbles: true });
    const form = document.querySelector('form[data-add-items-form]');
    const cancelBtn = document.querySelector('[data-add-cancel]') || document.querySelector('[data-add-cancel], [data-add-cancel-button]');


    
    document.querySelectorAll('[data-add-field]').forEach(el => el.dispatchEvent(ev));

    /* Year input: unchanged except re-render on change */
    if (yearInput) {
        yearInput.addEventListener('input', (e) => {
            const value = e.target.value.trim();

            if (value.length === 4) {
                const year = parseInt(value, 10);
                const currentYear = new Date().getFullYear();

                if (year < 2020 || year > currentYear) {
                    e.target.value = '';
                    showToast('error', `Please enter a valid year between 2020 and ${currentYear}.`);
                }
            }

            generateRows();
        });
    }

    /* Quantity: unchanged (digits only) */
    if (quantityInput) {
        quantityInput.addEventListener('keydown', (e) => {
            const invalidKeys = ['e', 'E', '+', '-', '.'];
            if (invalidKeys.includes(e.key)) {
                e.preventDefault();
            }
        });

        quantityInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            generateRows();
        });
    }

    /* GLA: digits only, max 4, reflect into rows */
    if (glaInput) {
        glaInput.addEventListener('input', (e) => {
            // keep only digits and max 4
            e.target.value = (e.target.value || '').replace(/\D/g, '').slice(0, 4);
            generateRows();
        });
        glaInput.addEventListener('blur', (e) => {
            // ensure non-empty GLA isn't longer than 4; nothing else to do
            e.target.value = (e.target.value || '').replace(/\D/g, '').slice(0, 4);
        });
    }

    /* Serial: allow alphanumeric, convert to uppercase, max 5 characters */
    if (serialInput) {
        serialInput.addEventListener('input', (e) => {
            // Allow letters and digits, convert to uppercase, limit to 5 chars
            e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 5);
            generateRows();
        });
    }

    /* Office Code: alphanumeric only, uppercase, limit to 4 */
    if (officeInput) {
        officeInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0,4);
            generateRows();
        });
    }

    // Re-generate when category or hidden ppe changes
    document.addEventListener('input', (e) => {
        if (e.target && (e.target.matches('[data-category-select]') || e.target.matches('input[name="category_code"], input[data-property-segment="category"]'))) {
            generateRows();
        }
    });

    // Build rows for preview
    function generateRows() {
        if (!generatedRowsContainer) return;

        const q = parseInt(quantityInput?.value || '0', 10) || 0;
        if (q <= 0 || q > 500) {
            generatedRowsContainer.innerHTML = '';
            return;
        }
        const year = (yearInput?.value || '').trim();

        // derive the visible Category code from the selected category name (not numeric id)
        let selectedCategoryText = '';
        if (categorySelect) {
            const opt = categorySelect.options[categorySelect.selectedIndex];
            if (opt) {
                // prefer a data attribute if available (many scripts add data-category-name)
                selectedCategoryText = opt.getAttribute('data-category-name') || opt.text || opt.value || '';
            }
        }
        // compute first 4 alphanumeric letters uppercase from the category name
        const categoryCode = (selectedCategoryText || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0,4);

        const startSerialRaw = (serialInput?.value || '').trim() || '1';
        const officeRaw = (officeInput?.value || '').trim();
        const glaRaw = (glaInput?.value || '').trim();

        // Determine serial width: preserve provided width or default 4
        const serialSeed = startSerialRaw || '1';
        const serialWidth = Math.max(serialSeed.length, 4);
        const serialStart = parseInt(serialSeed.replace(/^0+/, '') || '0', 10);

        const rows = [];
        for (let i = 0; i < q; i++) {
            const serialInt = serialStart + i;
            // pad serial to width
            let serial = String(serialInt).padStart(serialWidth, '0');
            // but allow the user-provided serial to be alphanumeric too — if original seed contained letters, use simple incrementing numerics for preview
            const pnDisplay = `${year || 'YYYY'}-${(categoryCode || '----')}-${glaRaw || 'GLA'}-${serial}-${officeRaw || 'OFF'}`;
            rows.push({ index: i + 1, year: year, categoryCode: categoryCode, gla: glaRaw, serial: serial, office: officeRaw, pn: pnDisplay });
        }

        // render rows: per-row Category input is readonly and shows first 4 uppercase letters
        generatedRowsContainer.innerHTML = rows.map(r => {
            return `
            <div class="flex items-center gap-4 per-row-panel" data-row-index="${r.index}">
              <div class="flex-none w-10 text-center">
                <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-700 font-medium">${r.index}</div>
              </div>

              <div class="flex-1 bg-indigo-50 rounded-lg px-3 py-2 flex items-center gap-2 flex-nowrap">
                <input type="text" name="property_numbers_components[${r.index}][year]" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-year" value="${r.year || ''}" placeholder="Year" />
                <div class="text-gray-500 select-none"> - </div>

                <input type="text" readonly name="property_numbers_components[${r.index}][category_code]" class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100 instance-part-category" value="${r.categoryCode || ''}" placeholder="Category" />
                <div class="text-gray-500 select-none"> - </div>

                <input type="text" name="property_numbers_components[${r.index}][gla]" inputmode="numeric" class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-gla" value="${r.gla || ''}" placeholder="GLA" />
                <div class="text-gray-500 select-none"> - </div>

                <input type="text" name="property_numbers_components[${r.index}][serial]" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-serial" value="${r.serial || ''}" placeholder="Serial" />
                <div class="text-gray-500 select-none"> - </div>

                <input type="text" name="property_numbers_components[${r.index}][office]" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-office" value="${r.office || ''}" placeholder="Office" />

                <div class="flex-none ml-2">
                  <button type="button" class="text-red-600 text-sm px-2 py-1 rounded-md hover:bg-red-50 per-row-remove-btn">Remove</button>
                </div>
              </div>
            </div>
            `;
        }).join('');

        // attach remove handlers for client-side only removal
        generatedRowsContainer.querySelectorAll('.per-row-remove-btn').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                const panel = ev.target.closest('.per-row-panel');
                if (panel) panel.remove();
            });
        });

        // attach serial editing sanitization per row (allow alphanumeric uppercase, limit 5)
        generatedRowsContainer.querySelectorAll('.instance-part-serial').forEach(inp => {
            inp.addEventListener('input', (e) => {
                e.target.value = (e.target.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0,5);
            });
        });

        // attach GLA sanitization per row (digits only, max 4)
        generatedRowsContainer.querySelectorAll('.instance-part-gla').forEach(inp => {
            inp.addEventListener('input', (e) => {
                e.target.value = (e.target.value || '').replace(/\D/g, '').slice(0,4);
            });
        });
    }

    if (form) {
      form.addEventListener('reset', () => {
        // reset quantity input to 1 (so next open shows 1 row)
        if (quantityInput) {
          quantityInput.value = '1';
        }
        // clear any generated rows and hide preview container if present
        if (generatedRowsContainer) generatedRowsContainer.innerHTML = '';
        // clear preview if preview component exists
        const previewList = form.querySelector('[data-add-preview-list]');
        const previewWrap = form.querySelector('[data-add-preview]');
        if (previewList) previewList.innerHTML = '';
        if (previewWrap) previewWrap.classList.add('hidden');
        // clear serial validation/feedback/sanitizers if present (some state held in modules)
        document.querySelectorAll('[data-serial-feedback]').forEach(f => { f.textContent = ''; f.classList.add('hidden'); });
      });
    }

    // Reset form when the create modal is opened so stale state does not persist
    window.addEventListener('open-modal', (e) => {
      // Accept both custom detail and global open - only reset when the create-item modal is opened
      const detail = e && e.detail ? e.detail : null;
      const openedName = detail || null;
      // We only want to reset when the create-item modal opens
      // If the event is fired with a string detail or without detail, also check the modal is present in DOM
      const shouldReset = (openedName === 'create-item') || (!openedName && document.querySelector('[data-modal][data-modal-name="create-item"]'));
      if (!shouldReset) return;
      try {
        // Reset native form
        form?.reset();
        form?.dispatchEvent(new Event('reset'));
        // re-run generateRows to reflect default quantity (1)
        generateRows();
      } catch (err) {
        console.warn('Failed to auto-reset create form when modal opened', err);
      }
    });

    // Cancel button: close modal and reset form (existing cancel logic is OK but ensure it resets quantity & generated rows)
    cancelBtn?.addEventListener('click', () => {
      if (form) {
        form.reset();
        form.dispatchEvent(new Event('reset'));
      }
      const previewList = form.querySelector('[data-add-preview-list]');
      const previewWrap = form.querySelector('[data-add-preview]');
      if (previewList) previewList.innerHTML = '';
      if (previewWrap) previewWrap.classList.add('hidden');
    });

    // initial generation on load if values present
    generateRows();
});

/* --- Toast utility --- */
function showToast(type, message) {
    if (type === 'error') {
        console.error(message);
    } else {
        console.log(message);
    }
}
</script>