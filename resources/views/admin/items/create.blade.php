<form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-property-form data-add-items-form>
    @csrf

    <div data-add-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-add-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <div>
        <x-input-label for="name" value="Item Name" />
        <x-text-input id="name" name="name" type="text"
                      class="mt-1 block w-full"
                      :value="old('name')" required autofocus data-add-field="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" value="Category" />
        <select id="category" name="category" class="mt-1 block w-full border rounded px-3 py-2" required data-category-select data-add-field="category">
            <option value="" disabled @selected(! old('category'))>-- Select Category --</option>
            @php $normalizedCategoryPpe = array_change_key_case($categoryPpeMap, CASE_LOWER); @endphp
            @foreach($categories as $cat)
                @php $optionValue = is_string($cat) ? $cat : strval($cat); @endphp
                <option value="{{ $optionValue }}" @selected(old('category') === $optionValue) data-ppe-code="{{ $normalizedCategoryPpe[strtolower($optionValue)] ?? '' }}">
                    {{ ucfirst($optionValue) }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <x-input-label for="year_procured" value="Year Procured" />
           <x-text-input id="year_procured" name="year_procured" type="number" maxlength="4" inputmode="numeric"
    class="mt-1 block w-full" :value="old('year_procured')" required min="2020" max="{{ date('Y') }}" 
    data-property-segment="year" data-add-field="year" />

            <x-input-error :messages="$errors->get('year_procured')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="ppe_code_display" value="PPE Code" />
            <x-text-input id="ppe_code_display" type="text" class="mt-1 block w-full bg-gray-100" readonly
                          :value="old('ppe_code')" data-ppe-display />
            <input type="hidden" name="ppe_code" value="{{ old('ppe_code', $normalizedCategoryPpe[strtolower(old('category', ''))] ?? '') }}" data-property-segment="ppe">
        </div>
        <div>
            <x-input-label for="start_serial" value="Starting Serial" />
            <x-text-input id="start_serial" name="start_serial" type="text" maxlength="6" inputmode="numeric"
                          class="mt-1 block w-full" :value="old('start_serial')" required
                          data-property-segment="serial" data-add-field="serial" />
            <p class="mt-1 text-xs hidden" data-serial-feedback></p>
            <x-input-error :messages="$errors->get('start_serial')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="office_code" value="Office Code" />
            <x-text-input id="office_code" name="office_code" type="text" maxlength="4" inputmode="numeric"
                        class="mt-1 block w-full" :value="old('office_code')" required
                        data-property-segment="office" data-add-field="office" />
                        <p class="mt-1 text-xs text-red-600 hidden" data-office-error>Office code must be 1–4 digits.</p>
            <x-input-error :messages="$errors->get('office_code')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="quantity" value="Quantity" />
            <x-text-input id="quantity" name="quantity" type="number" min="1" step="1"
                          class="mt-1 block w-full"
                          :value="old('quantity', 1)" required data-add-field="quantity" />
            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
        </div>
       <div>
            <x-input-label for="property_preview-{{ $item->id ?? 'new' }}" value="Property Number Preview" />
            <x-text-input id="property_preview-{{ $item->id ?? 'new' }}" type="text" class="mt-1 block w-full bg-gray-100" readonly
                        value="{{ old('property_preview', $primaryInstance->property_number ?? '') }}" data-property-preview />
        </div>

    </div>

    <div data-add-preview class="hidden rounded-lg border border-dashed border-purple-300 bg-purple-50 px-4 py-3 text-xs text-purple-900">
        <div class="font-semibold">Preview</div>
        <div data-add-preview-list class="mt-1 space-y-1"></div>
    </div>

    <div>
        <x-input-label for="description" value="Description (optional)" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border rounded px-3 py-2" data-add-field="description">{{ old('description') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="photo" value="Photo (optional)" />
        <input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
    </div>

    <div class="flex justify-end gap-3 pt-2">
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
            type="submit" data-add-submit>Save</x-button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const yearInput = document.querySelector('#year_procured');
        
        // Ensure the year input is validated after the user finishes typing the 4 digits
        yearInput.addEventListener('input', (e) => {
            const value = e.target.value.trim();
            
            // Allow only 4 digits
            if (value.length === 4) {
                const year = parseInt(value, 10);
                const currentYear = new Date().getFullYear();

                // Check if the year is valid
                if (year < 2020 || year > currentYear) {
                    e.target.value = '';  // Reset the input to blank
                    showToast('error', 'Please enter a valid year between 2020 and ' + currentYear + '.');
                }
            }
        });
    });

    // Function to show the toast message (for invalid year)
    function showToast(type, message) {
        if (type === 'error') {
            console.error(message);  // For error, log it to the console
        } else {
            console.log(message);  // For success, log it
        }
    }
</script>
