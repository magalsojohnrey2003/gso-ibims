<form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-property-form data-add-items-form> 
    @csrf

    <div data-add-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-add-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <!-- Step 1: Basic Information -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</div>
            <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" value="Item Name" />
                <!-- Visible input even when not focused -->
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
                    <x-input-label for="category" value="Category" />
                    <select id="category" name="category" class="mt-1 block w-full min-w-0 appearance-none border rounded px-3 py-2" required data-category-select>
                    <!-- options will be rendered by JS; keep server-side selected value only if you want initial selection -->
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

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
            <h4 class="text-lg font-semibold text-gray-900">Identification & Codes</h4>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <x-input-label for="year_procured" value="Year Procured" />
                <x-text-input
                    id="year_procured"
                    name="year_procured"
                    type="number"
                    maxlength="4"
                    inputmode="numeric"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                    :value="old('year_procured')"
                    required
                    min="2020"
                    max="{{ date('Y') }}"
                    data-property-segment="year"
                    data-add-field="year"
                />
                <x-input-error :messages="$errors->get('year_procured')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ppe_code_display" value="PPE Code" />
                <x-text-input
                    id="ppe_code_display"
                    type="text"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm"
                    readonly
                    :value="old('ppe_code')"
                    data-ppe-display
                />
                <input type="hidden" name="ppe_code" value="{{ old('ppe_code', $normalizedCategoryPpe[strtolower(old('category', ''))] ?? '') }}" data-property-segment="ppe">
            </div>

            <div>
                <x-input-label for="start_serial" value="Starting Serial" />
                <x-text-input
                    id="start_serial"
                    name="start_serial"
                    type="text"
                    maxlength="6"
                    inputmode="numeric"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                    :value="old('start_serial')"
                    required
                    data-property-segment="serial"
                    data-add-field="serial"
                />
                <p class="mt-1 text-xs hidden" data-serial-feedback></p>
                <x-input-error :messages="$errors->get('start_serial')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="office_code" value="Office Code" />
                <x-text-input
                    id="office_code"
                    name="office_code"
                    type="text"
                    maxlength="4"
                    inputmode="numeric"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm focus:border-blue-400 focus:ring focus:ring-blue-100 transition"
                    :value="old('office_code')"
                    required
                    data-property-segment="office"
                    data-add-field="office"
                />
                <p class="mt-1 text-xs text-red-600 hidden" data-office-error>Office code must be 1–4 digits.</p>
                <x-input-error :messages="$errors->get('office_code')" class="mt-2" />
            </div>
        </div>

        <!-- Property Number Preview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="property_preview-{{ $item->id ?? 'new' }}" value="Property Number Preview" />
                <x-text-input
                    id="property_preview-{{ $item->id ?? 'new' }}"
                    type="text"
                    class="mt-1 block w-full bg-gray-100 border border-gray-300 text-gray-900 rounded-md px-3 py-2 shadow-sm"
                    readonly
                    value="{{ old('property_preview', $primaryInstance->property_number ?? '') }}"
                    data-property-preview
                />
            </div>
        </div>

        <!-- Preview Section (kept dashed border preview as it is useful visual) -->
        <div data-add-preview class="hidden rounded-lg border border-dashed border-purple-300 bg-purple-50 px-4 py-3 text-xs text-purple-900 mt-4">
            <div class="font-semibold">Item Summary</div>
            <div data-add-preview-list class="mt-1 space-y-1"></div>
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
    const serialInput = document.querySelector('#start_serial');
    const officeInput = document.querySelector('#office_code');

    /* --- 1️⃣ Restrict YEAR input to 4 valid digits --- */
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
        });
    }

    /* --- 2️⃣ Restrict QUANTITY to digits only --- */
    if (quantityInput) {
        quantityInput.addEventListener('keydown', (e) => {
            const invalidKeys = ['e', 'E', '+', '-', '.'];
            if (invalidKeys.includes(e.key)) {
                e.preventDefault();
            }
        });

        quantityInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }

    /* --- 3️⃣ Restrict SERIAL to digits only (NO letters, symbols, etc.) --- */
    if (serialInput) {
        serialInput.addEventListener('keydown', (e) => {
            // Allow only digits and control keys (backspace, delete, arrows, tab)
            const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
            if (!/[0-9]/.test(e.key) && !allowedKeys.includes(e.key)) {
                e.preventDefault();
            }
        });

        serialInput.addEventListener('input', (e) => {
            // Strip any non-digit characters if pasted
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }
    /* --- 4️⃣ Restrict OFFICE CODE to alphanumeric only --- */
    if (officeInput) {
        officeInput.addEventListener('keydown', (e) => {
            const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
            if (!/[a-zA-Z0-9]/.test(e.key) && !allowedKeys.includes(e.key)) {
                e.preventDefault();
            }
        });

        officeInput.addEventListener('input', (e) => {
            // Remove anything not letter or digit (covers pasting too)
            e.target.value = e.target.value.replace(/[^a-zA-Z0-9]/g, '');
        });
    }

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

