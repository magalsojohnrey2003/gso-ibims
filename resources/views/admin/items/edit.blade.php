@php
    $normalizedCategoryPpe = array_change_key_case($categoryPpeMap, CASE_LOWER);
    $primaryInstance = $item->instances->first();
    $ppeForCategory = $normalizedCategoryPpe[strtolower($item->category)] ?? ($primaryInstance->ppe_code ?? '');
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
        <select id="category-{{ $item->id }}" name="category"
                class="mt-1 block w-full min-w-0 appearance-none border rounded px-3 py-2"
                data-category-select data-field="category" data-edit-field="category">
            <!-- options rendered by JS -->
        </select>

        <x-input-error :messages="$errors->get('category')" class="mt-2" />
      </div>
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

            <div class="flex-1 bg-indigo-50 rounded-lg px-4 py-3 flex items-center gap-3 flex-nowrap">
              <input type="text" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-year" value="{{ $inst->year_procured ?? '' }}" placeholder="Year" />
              <div class="text-gray-500 select-none"> - </div>
              <input type="text" class="w-16 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-ppe" value="{{ $inst->ppe_code ?? '' }}" placeholder="PPE" />
              <div class="text-gray-500 select-none"> - </div>
              <input type="text" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-serial" value="{{ $inst->serial ?? '' }}" placeholder="Serial" />
              <div class="text-gray-500 select-none"> - </div>
              <input type="text" class="w-20 text-center text-sm rounded-md border px-2 py-1 bg-white instance-part-office" value="{{ $inst->office_code ?? '' }}" placeholder="Office" />
              <div class="flex-none ml-2">
                <button type="button" class="text-red-600 text-sm px-2 py-1 rounded-md hover:bg-red-50 instance-remove-btn">Remove</button>
              </div>
            </div>

            <div class="flex-none ml-4 text-xs text-yellow-700 instance-status">
              {{ $inst->property_number }}
            </div>
          </div>
        @endforeach
      </div>

      <p class="mt-2 text-xs text-gray-500">You may edit components in-place. Valid changes will save automatically.</p>
    </div>

    

    <!-- Step 3: Additional Details (collapsible) -->
    <div class="bg-gray-50 shadow-md hover:shadow-lg transition rounded-lg">
      <button
        type="button"
        data-step3-header
        aria-expanded="false"
        aria-controls="edit-step3-body-{{ $item->id }}"
        class="w-full text-left p-4 flex items-center justify-between focus:outline-none"
      >
        <div class="flex items-center space-x-3">
          <div class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
          <h4 class="text-lg font-semibold text-gray-900">Additional Details</h4>
        </div>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" data-step3-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
        const ppeInput = document.querySelector('#ppe_code_display-{{ $item->id ?? 'new' }}');
        const serialInput = document.querySelector('#serial-{{ $item->id ?? 'new' }}');
        const officeInput = document.querySelector('#office_code-{{ $item->id ?? 'new' }}');
        const previewInput = document.querySelector('#property_preview-{{ $item->id ?? 'new' }}');

        // Function to update the Property Number Preview
        function updatePropertyNumberPreview() {
        const year = (yearInput?.value || '').trim();
        const ppe = (ppeInput?.value || '').trim();
        const serial = (serialInput?.value || '').trim();
        const officeRaw = (officeInput?.value || '').trim();
        const office = officeRaw ? officeRaw.padStart(4, '0') : '';

        // Only show preview when we have year(4), ppe(2+), serial (non-empty), and office (1-4 digits padded)
         if (year.length === 4 && ppe && serial && officeRaw.length >= 1 && officeRaw.length <= 4) {
            const propertyNumber = `${year}-${ppe}-${serial}-${officeRaw}`;
            if (previewInput) previewInput.value = propertyNumber;
        } else if (previewInput) {
            // clear if incomplete
            previewInput.value = '';
        }                                                                   
    }


        // Attach event listeners to the input fields
        yearInput.addEventListener('input', updatePropertyNumberPreview);
        ppeInput.addEventListener('input', updatePropertyNumberPreview);
        serialInput.addEventListener('input', updatePropertyNumberPreview);
        officeInput.addEventListener('input', updatePropertyNumberPreview);

        // Initial call to update the preview for Edit Item (if values exist)
        if (yearInput.value.trim() && ppeInput.value.trim() && serialInput.value.trim() && officeInput.value.trim()) {
            updatePropertyNumberPreview();
        }
    });

</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  function restoreForm(modalName) {
    if (!modalName) return;
    const form = document.querySelector(`form[data-modal-name="${modalName}"]`);
    if (!form) return;

    // Reset to initial HTML values
    form.reset();

    // Clear file inputs (they don't get reset to a file for security)
    form.querySelectorAll('input[type="file"]').forEach(el => el.value = '');

    // Restore photo from data-original-src if present
    const photo = form.querySelector('[data-edit-photo]');
    if (photo) {
      const orig = photo.getAttribute('data-original-src');
      if (orig) photo.src = orig;
    }

    // Hide inline feedback boxes
    const feedback = form.querySelector('[data-edit-feedback]');
    if (feedback) feedback.classList.add('hidden');
    const error = form.querySelector('[data-edit-error]');
    if (error) error.classList.add('hidden');

    // Trigger inputs change so preview and other listeners re-calc
    form.querySelectorAll('input, select, textarea').forEach(el => {
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  // When a modal closes, reset that form
  window.addEventListener('close-modal', (e) => {
    const modalName = e?.detail;
    restoreForm(modalName);
  });

  // When a modal opens, also reset (ensures repeated opens always show server values)
  window.addEventListener('open-modal', (e) => {
    const modalName = e?.detail;
    restoreForm(modalName);
  });
});

</script>


