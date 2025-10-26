<!-- resources/views/admin/items/edit.blade.php -->
@php
    // Create map id => name for quick lookup
    $categoryMap = collect($categories ?? [])->filter()->keyBy('id')->map(fn($c) => $c['name'])->toArray();

    // If item->category is numeric id, display the mapped name; otherwise display the stored category string.
    $displayCategoryName = $item->category;
    if (is_numeric($item->category) && isset($categoryMap[(int)$item->category])) {
        $displayCategoryName = $categoryMap[(int)$item->category];
    } else {
        $displayCategoryName = $displayCategoryName ?? '';
    }

    $normalizedCategoryCode = array_change_key_case($categoryCodeMap ?? [], CASE_LOWER);
    $primaryInstance = $item->instances->first();
    $categoryCodeForCategory = '';
    if (is_numeric($item->category)) {
        $two = str_pad((int) $item->category, 2, '0', STR_PAD_LEFT);
        $categoryCodeForCategory = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/','', $two)), 0, 4);
    } else {
        $raw = $normalizedCategoryCode[strtolower($item->category)] ?? ($primaryInstance->category_code ?? '');
        $categoryCodeForCategory = $raw ?? '';
    }
@endphp

<form method="POST" action="{{ route('items.update', $item->id) }}" enctype="multipart/form-data"
      class="space-y-6" data-property-form data-edit-item-form data-modal-name="edit-item-{{ $item->id }}">
    @csrf
    @method('PUT')

    <div data-edit-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-edit-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <input type="hidden" name="item_instance_id" value="{{ $primaryInstance->id ?? '' }}">

    <!-- Step 1: Basic Information -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
      <div class="flex items-center mb-4">
        <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</div>
        <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
      </div>

      <div>
        <x-input-label for="name-{{ $item->id }}" value="Item Name" />
        <x-text-input id="name-{{ $item->id }}" type="text" name="name"
                      value="{{ old('name', $item->name) }}"
                      class="mt-1 block w-full"
                      required data-edit-field="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
      </div>

      <div class="mt-4">
        <x-input-label for="category-{{ $item->id }}" value="Category" />
        <!-- Category is finalized. Show readonly display and still submit the value via hidden input. -->
        <input type="text" id="category-{{ $item->id }}" aria-readonly="true"
               class="mt-1 block w-full min-w-0 appearance-none border rounded px-3 py-2 bg-gray-100 text-gray-700"
               value="{{ $displayCategoryName }}" readonly />

        <!-- Hidden category code populated by category JS -->
        <input type="hidden" name="category_code" data-property-segment="category" value="{{ old('category_code', $categoryCodeForCategory) }}" />

        <input type="hidden" name="gla" data-property-segment="gla" value="{{ old('gla', '') }}" />

        <x-input-error :messages="$errors->get('category')" class="mt-2" />
      </div>

    <!-- Step 2: Identification & Codes -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg p-4">
      <div class="flex items-center mb-4">
        <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</div>
        <h4 class="text-lg font-semibold text-gray-900">Existing Property Numbers</h4>
      </div>

      <p class="text-sm text-gray-600 mb-3">Edit or remove existing property numbers for this item. Changes save automatically.</p>

      <div id="edit_instances_container" class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white" aria-live="polite">
        @foreach ($item->instances as $inst)
          <div class="flex items-start gap-4 edit-instance-row" data-instance-id="{{ $inst->id }}">
            <div class="flex-none w-10 text-center">
              <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-medium">{{ $loop->iteration }}</div>
            </div>

            <div class="flex-1 bg-indigo-50 rounded-lg px-3 py-2 flex items-center gap-2 flex-wrap">
              <input type="text" class="w-20 sm:w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-year" value="{{ $inst->year_procured ?? '' }}" placeholder="Year" />
              <div class="text-gray-500 select-none"> - </div>

              <input type="text" readonly class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-gray-100 instance-part-category" value="{{ $inst->category_code ?? $inst->category_id ?? '' }}" placeholder="Category" />
              <div class="text-gray-500 select-none"> - </div>

              <input type="text" class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-gla" value="{{ $inst->gla ?? '' }}" placeholder="GLA" />
              <div class="text-gray-500 select-none"> - </div>

              <input type="text" class="w-20 sm:w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-serial" value="{{ $inst->serial ?? '' }}" placeholder="Serial" />
              <div class="text-gray-500 select-none"> - </div>

              <input type="text" class="w-20 sm:w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-office" value="{{ $inst->office_code ?? '' }}" placeholder="Office" />
              <div class="flex-none ml-2">
                <button type="button" class="instance-remove-btn inline-flex items-center justify-center text-red-600 p-1 rounded hover:bg-red-50" aria-label="Remove instance">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>

            <div class="flex-none ml-4 text-xs text-yellow-700 instance-status">
              {{ $inst->property_number }}
            </div>
          </div>
        @endforeach
      </div>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="edit-step3-body-{{ $item->id }}" data-step3-body class="p-4 max-h-0 overflow-hidden opacity-0">
        <div>
          <x-input-label for="description-{{ $item->id }}" value="Description" />
          <textarea id="description-{{ $item->id }}" name="description" rows="3"
                    class="mt-1 block w-full border rounded px-3 py-2"
                    data-edit-field="description">{{ old('description', $primaryInstance?->notes) }}</textarea>
          <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div><br>

        @php
          $existingPath = $primaryInstance->photo ?? $item->photo ?? '';
          $existingUrl = '';
          if ($existingPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($existingPath)) {
              $existingUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($existingPath);
          } elseif ($existingPath && str_starts_with($existingPath, 'http')) {
              $existingUrl = $existingPath;
          }
        @endphp

        <div class="mb-3">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Photo Upload</label>
          <input id="photo-{{ $item->id }}" name="photo" type="file" accept="image/*"
                data-filepond="true"
                @if($existingUrl) data-initial-url="{{ $existingUrl }}" @endif
                data-preview-height="120"
                data-thumb-width="160" />
          <input type="hidden" name="existing_photo" value="{{ $existingPath }}" />
        </div>
      </div> <!-- /#edit-step3-body -->
    </div> <!-- /.step3 wrapper -->

    <!-- Action buttons (separate from Step 3) -->
    <div class="mt-4 border-t pt-4 flex justify-end gap-3">
      <x-button
        variant="secondary"
        iconName="x-mark"
        type="button"
        data-edit-cancel
        x-on:click="$dispatch('close-modal', 'edit-item-{{ $item->id }}')">
        Cancel
      </x-button>

      <x-button iconName="arrow-path" type="submit" data-edit-submit>Update</x-button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const yearInput = document.querySelector('#year_procured-{{ $item->id }}');
        
        // Ensure the year input is validated after the user finishes typing the 4 digits
        if (yearInput) {
            yearInput.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                
                // Allow only 4 digits
                if (value.length === 4) {
                    const year = parseInt(value, 10);
                    const currentYear = new Date().getFullYear();

                    // Check if the year is valid
                    if (year < 2020 || year > currentYear) {
                        e.target.value = '';  // Reset the input to blank if invalid
                        showToast('error', 'Please enter a valid year between 2020 and ' + currentYear + '.');
                    }
                }
            });
        }
    });

    // Function to show the toast message (for invalid year)
    function showToast(type, message) {
        if (type === 'error') {
            console.error(message);  // For error, log it to the console
        } else {
            console.log(message);  // For success, log it
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const yearInput = document.querySelector('#year_procured-{{ $item->id ?? 'new' }}');
        // Read hidden PPE input (populated by category JS)
        const categoryInput = document.querySelector('input[name="category_code"], input[data-property-segment="category"]');
        const serialInput = document.querySelector('#serial-{{ $item->id ?? 'new' }}');
        const officeInput = document.querySelector('#office_code-{{ $item->id ?? 'new' }}');
        const previewInput = document.querySelector('#property_preview-{{ $item->id ?? 'new' }}');
````````const glaInput = document.querySelector('#gla-{{ $item->id ?? 'new' }}') || document.querySelector('input[name="gla"], input[data-property-segment="gla"]');

        // Function to update the Property Number Preview
        function updatePropertyNumberPreview() {
        const year = (yearInput?.value || '').trim();
        const ppe = (categoryInput?.value || '').trim();
        const gla = (glaInput?.value || '').trim();
        const serial = (serialInput?.value || '').trim();
        const officeRaw = (officeInput?.value || '').trim();
        const office = officeRaw ? officeRaw.padStart(4, '0') : '';

        // Only show preview when we have year(4), ppe(2+), serial (non-empty), and office (1-4 digits padded)
         if (year.length === 4 && ppe && gla && serial && officeRaw.length >= 1 && officeRaw.length <= 4) {
            const propertyNumber = `${year}-${ppe}-${gla}-${serial}-${officeRaw}`;
            if (previewInput) previewInput.value = propertyNumber;
        } else if (previewInput) {
            // clear if incomplete
            previewInput.value = '';
        }                                                                   
    }


        // Attach event listeners to the input fields
        if (yearInput) yearInput.addEventListener('input', updatePropertyNumberPreview);
        if (categoryInput) categoryInput.addEventListener('input', updatePropertyNumberPreview);
        if (serialInput) serialInput.addEventListener('input', updatePropertyNumberPreview);
        if (officeInput) officeInput.addEventListener('input', updatePropertyNumberPreview);

        // Initial call to update the preview for Edit Item (if values exist)
        if (yearInput?.value.trim() && categoryInput?.value.trim() && serialInput?.value.trim() && officeInput?.value.trim()) {
            updatePropertyNumberPreview();
        }
    });

</script>