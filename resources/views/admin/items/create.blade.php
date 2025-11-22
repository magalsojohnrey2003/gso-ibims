<!-- resources/views/admin/items/create.blade.php -->
<style>
/* Hide native dropdown arrows only inside the Add New Item modal */
#create-item-form select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: none !important;
    /* Ensure long labels don't visually overflow */
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    /* Max width for very long option names */
    max-width: 100%;
}
#create-item-form select::-ms-expand { display: none; }

/* Responsive wrapping for long category/option names in dropdowns */
#create-item-form select option {
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
}
</style>
<form id="create-item-form" action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-property-form data-add-items-form data-accordion-group="create-item">
@csrf

<div data-add-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
<div data-add-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

<!-- Step 1: Item Information -->
<div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
    <button
        type="button"
        class="w-full text-left focus:outline-none"
        data-accordion-trigger
        data-accordion-target="create-item-info"
        aria-expanded="true">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">1</div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Item Information</h4>
            </div>
            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
            </svg>
        </div>
    </button>

    <div id="create-item-info" class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800" data-accordion-panel data-accordion-open>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" value="Item Name" />
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="input-field mt-1"
                    :value="old('name')"
                    required
                    autofocus
                    data-add-field="name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="quantity" value="Quantity" />
                <x-text-input
                    id="quantity"
                    name="quantity"
                    type="number"
                    min="1"
                    step="1"
                    class="input-field mt-1"
                    :value="old('quantity', 1)"
                    required
                    data-add-field="quantity"
                />
                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" value="Description" />
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="gov-input block w-full px-3 py-2 text-sm transition duration-200 mt-1"
                    data-add-field="description"
                >{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Identification & Codes -->
<div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
    <button
        type="button"
        class="w-full text-left focus:outline-none"
        data-accordion-trigger
        data-accordion-target="create-property-config"
        aria-expanded="false">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">2</div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Generate Property Numbers</h4>
            </div>
            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
            </svg>
        </div>
    </button>

    <div id="create-property-config" class="p-4 border-t border-gray-100 space-y-6" data-accordion-panel>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="flex flex-col">
                <x-input-label for="year_procured" value="Year Procured" />
        <select id="year_procured" name="year_procured"
            class="gov-input block w-full px-3 py-2 text-sm transition duration-200 mt-1"
            data-property-segment="year"
            data-add-field="year">
                    <option value="">-- Year --</option>
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

            <div class="flex flex-col">
                <x-input-label for="category" value="Category" />
        <select id="category" name="category"
            class="gov-input block w-full px-3 py-2 text-sm transition duration-200 mt-1"
            data-category-select data-field="category" data-add-field="category">
                </select>
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
            </div>

            <div class="flex flex-col">
                <x-input-label for="gla" value="GLA" />
                <select
                    id="gla"
                    name="gla"
                    class="gov-input block w-full px-3 py-2 text-sm transition duration-200 mt-1 disabled:cursor-not-allowed"
                    data-gla-select
                    data-add-field="gla"
                    disabled>
                    <option value="">-- GLA --</option>
                </select>
                <x-input-error :messages="$errors->get('gla')" class="mt-2" />
            </div>

            <div class="flex flex-col">
                <x-input-label for="serial" value="Serial" />
                <x-text-input
                    id="serial"
                    name="serial"
                    type="text"
                    maxlength="5"
                    class="input-field mt-1"
                    :value="old('serial')"
                    placeholder="e.g. A01"
                    data-property-segment="serial"
                    data-add-field="serial"
                />
                <p class="mt-1 text-xs hidden" data-serial-feedback></p>
                <x-input-error :messages="$errors->get('serial')" class="mt-2" />
            </div>

            <div class="flex flex-col">
                <x-input-label for="office" value="Office" />
                <select
                    id="office"
                    name="office"
                    class="gov-input block w-full px-3 py-2 text-sm transition duration-200 mt-1"
                    data-add-field="office"
                    data-property-segment="office"
                    data-office-select>
                    <option value="">- Office -</option>
                </select>
                <x-input-error :messages="$errors->get('office')" class="mt-2" />
            </div>
        </div>

        <input type="hidden" name="category_code" data-property-segment="category" value="" />

        <div class="bg-gray-50 shadow-md rounded-lg p-4">
            <div class="flex items-center mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Property Number Rows</h4>
            </div>

            <div class="text-xs text-gray-600 mb-2">
                Rows will be generated Year, Category, GLA, Serial and Office. You can edit rows before saving.
            </div>

            <div
                id="generated_rows_container"
                class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white"
                data-property-rows-container
                aria-live="polite">
            </div>

            <template data-property-row-template>
                <div class="flex items-center gap-2 per-row-panel bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-3 py-3" data-property-row>
                    <div class="flex-none w-8 text-center">
                        <div class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-200 font-medium text-sm" data-row-index>1</div>
                    </div>

                    <div class="flex items-center gap-2 flex-1" data-row-fields>
                        <input type="text" readonly class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200" placeholder="Year" data-row-field="year" data-row-name="year" inputmode="numeric" maxlength="4" />
                        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                        <input type="text" readonly class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200" placeholder="Category" data-row-field="category" data-row-name="category_code" maxlength="4" />
                        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                        <input type="text" readonly class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200" placeholder="GLA" data-row-field="gla" data-row-name="gla" inputmode="numeric" maxlength="4" />
                        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                        <input type="text" class="gov-input rounded-md w-20 text-center text-sm px-2 py-1 transition duration-200" placeholder="Serial" data-row-field="serial" data-row-name="serial" maxlength="5" />
                        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                        <input type="text" readonly class="gov-input rounded-md w-16 text-center text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700/80 transition duration-200" placeholder="Office" data-row-field="office" data-row-name="office" maxlength="4" inputmode="numeric" />
                    </div>
                    
                    <button 
                        type="button" 
                        class="flex-none inline-flex items-center justify-center text-red-600 hover:text-red-700 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" 
                        data-row-remove>
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- Step 3: Serial and Model No. -->
<div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item data-serial-model-section>
    <button
        type="button"
        class="w-full text-left focus:outline-none"
        data-accordion-trigger
        data-serial-model-trigger
        data-accordion-target="create-serial-model"
        aria-expanded="false">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Serial and Model No.</h4>
            </div>
            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
            </svg>
        </div>
    </button>

    <div id="create-serial-model" class="p-4 border-t border-gray-100 space-y-4" data-accordion-panel data-serial-model-panel>
        <p class="text-sm text-gray-600" data-serial-model-message>
            Serial and model numbers become available once property number rows are completed.
        </p>

        <!-- Generate Model No. (for all rows) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Generate Model No. (for all rows)</label>
                <input
                    type="text"
                    maxlength="100"
                    class="gov-input w-full px-3 py-2 text-sm uppercase transition duration-200"
                    placeholder="Type a model number to apply to all rows"
                    data-model-generator>
            </div>
            <div class="text-xs text-gray-500 md:text-right">Applies to every Model No. field below.</div>
        </div>

        <div
            class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white"
            data-serial-model-container
            aria-live="polite">
        </div>

        <template data-serial-model-template>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-indigo-50 dark:bg-indigo-900/30 space-y-3" data-serial-model-row>
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-serial-model-pn>PROPERTY-NUMBER</div>
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Serial No.</label>
                        <input
                            type="text"
                            maxlength="100"
                            class="gov-input w-full px-3 py-2 text-sm uppercase transition duration-200"
                            data-serial-model-field="serial_no" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Model No.</label>
                        <input
                            type="text"
                            maxlength="100"
                            class="gov-input w-full px-3 py-2 text-sm uppercase transition duration-200"
                            data-serial-model-field="model_no" />
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- Step 4: Additional Details -->
<div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg" data-accordion-item>
    <button
        type="button"
        class="w-full text-left focus:outline-none"
        data-accordion-trigger
        data-accordion-target="create-additional-details"
        aria-expanded="false">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">4</div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Additional Details</h4>
            </div>
            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
            </svg>
        </div>
    </button>

    <div id="create-additional-details" class="p-4 border-t border-gray-100 space-y-4" data-accordion-panel>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Photo Upload</label>
            <input id="photo" name="photo" type="file" accept="image/*"
                   data-filepond="true"
                   data-preview-height="120"
                   data-thumb-width="160" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="acquisition_date" value="Acquisition Date" />
                <x-text-input
                    id="acquisition_date"
                    name="acquisition_date"
                    type="date"
                    class="input-field mt-1"
                    :value="old('acquisition_date')"
                    data-add-field="acquisition_date"
                />
                <x-input-error :messages="$errors->get('acquisition_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="acquisition_cost" value="Acquisition Cost" />
                <x-text-input
                    id="acquisition_cost"
                    name="acquisition_cost"
                    type="text"
                    inputmode="decimal"
                    class="input-field mt-1"
                    :value="old('acquisition_cost')"
                    placeholder="0"
                    data-currency-format="php"
                    data-add-field="acquisition_cost"
                />
                <x-input-error :messages="$errors->get('acquisition_cost')" class="mt-2" />
            </div>
        </div>
    </div>
</div>

<div class="mt-4 pt-2 flex justify-end gap-3 sticky bottom-0 z-20 pb-2">
    <x-button
        variant="primary"
        iconName="document-check"
        type="submit"
        data-add-submit>
        Save
    </x-button>
</div>
</form>

