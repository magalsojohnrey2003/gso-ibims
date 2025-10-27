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
                maxlength="5"
                inputmode="text"
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

      <div
        id="generated_rows_container"
        class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white"
        data-property-rows-container
        aria-live="polite">
      </div>

      <template data-property-row-template>
        <div class="flex items-center gap-4 per-row-panel" data-property-row>
          <div class="flex-none w-10 text-center">
            <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-700 font-medium" data-row-index>1</div>
          </div>

          <div class="flex-1 bg-indigo-50 rounded-lg px-3 py-2 flex items-center gap-2 flex-nowrap">
            <input type="text" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white" placeholder="Year" data-row-field="year" data-row-name="year" inputmode="numeric" maxlength="4" />
            <div class="text-gray-500 select-none"> - </div>

            <input type="text" readonly class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100" placeholder="Category" data-row-field="category" data-row-name="category_code" maxlength="4" />
            <div class="text-gray-500 select-none"> - </div>

            <input type="text" class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white" placeholder="GLA" data-row-field="gla" data-row-name="gla" inputmode="numeric" maxlength="4" />
            <div class="text-gray-500 select-none"> - </div>

            <input type="text" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white" placeholder="Serial" data-row-field="serial" data-row-name="serial" maxlength="5" />
            <div class="text-gray-500 select-none"> - </div>

            <input type="text" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white" placeholder="Office" data-row-field="office" data-row-name="office" maxlength="4" inputmode="numeric" />

            <div class="flex-none ml-2">
              <button type="button" class="text-red-600 text-sm px-2 py-1 rounded-md hover:bg-red-50" data-row-remove>Remove</button>
            </div>
          </div>
        </div>
      </template>
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

 
