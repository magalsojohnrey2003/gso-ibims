<!-- resources/views/admin/items/create.blade.php -->
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
                    class="block w-full px-3 py-2 text-sm border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 mt-1"
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
            class="block w-full px-3 py-2 text-sm border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 mt-1"
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

            <div class="flex flex-col">
                <x-input-label for="category" value="Category" />
        <select id="category" name="category"
            class="block w-full px-3 py-2 text-sm border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 mt-1"
            data-category-select data-field="category" data-add-field="category">
                </select>
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
            </div>

            <div class="flex flex-col">
                <x-input-label for="gla" value="GLA" />
                <x-text-input
                    id="gla"
                    name="gla"
                    type="text"
                    maxlength="4"
                    inputmode="numeric"
                    pattern="\d{1,4}"
                    class="input-field mt-1"
                    :value="old('gla')"
                    placeholder="digits only (1-4)"
                    data-add-field="gla"
                />
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
                    class="block w-full px-3 py-2 text-sm border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 mt-1"
                    data-add-field="office"
                    data-property-segment="office"
                    data-office-select>
                    <option value="">- Select Office -</option>
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
                <div class="flex items-center gap-3 per-row-panel" data-property-row>
                    <div class="flex-none w-10 text-center pt-2">
                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-700 font-medium" data-row-index>1</div>
                    </div>

                    <div class="flex-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-3 py-2 overflow-x-auto">
                        <div class="flex items-center gap-2 flex-nowrap" data-row-fields>
                            <input type="text" class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="Year" data-row-field="year" data-row-name="year" inputmode="numeric" maxlength="4" />
                            <div class="text-gray-500 dark:text-gray-400 select-none">-</div>

                            <input type="text" readonly class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-300" placeholder="Category" data-row-field="category" data-row-name="category_code" maxlength="4" />
                            <div class="text-gray-500 dark:text-gray-400 select-none">-</div>

                            <input type="text" class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="GLA" data-row-field="gla" data-row-name="gla" inputmode="numeric" maxlength="4" />
                            <div class="text-gray-500 dark:text-gray-400 select-none">-</div>

                            <input type="text" class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="Serial" data-row-field="serial" data-row-name="serial" maxlength="5" />
                            <div class="text-gray-500 dark:text-gray-400 select-none">-</div>

                            <input type="text" class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="Office" data-row-field="office" data-row-name="office" maxlength="4" inputmode="numeric" />
                            <button 
                                type="button" 
                                class="text-red-600 text-sm px-2 py-1 rounded-md hover:bg-red-50" 
                                data-row-remove>
                                <i class="fas fa-trash"></i>
                            </button>
                            <div class="flex-1"></div>
                        </div>     
                    </div>
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
                            maxlength="4"
                            class="w-full px-3 py-2 border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200"
                            data-serial-model-field="serial_no" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Model No.</label>
                        <input
                            type="text"
                            maxlength="15"
                            class="w-full px-3 py-2 border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200"
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

<div class="mt-4 border-t pt-4 flex justify-end gap-3 sticky bottom-0 bg-white z-20">
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

