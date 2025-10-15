{{-- resources/views/admin/items/_manual_modal.blade.php --}}
<x-modal name="manual-property-entry" maxWidth="2xl">
  <div class="w-full bg-white shadow-lg overflow-hidden">
    <div class="bg-emerald-600 text-white px-6 py-5">
      <h3 class="text-2xl font-bold flex items-center">
        <i class="fas fa-key mr-2"></i>
        Manual Property Number Entry
      </h3>
      <p class="text-emerald-100 mt-2 text-sm leading-relaxed">Create item instances by entering property numbers manually or pasting a list.</p>
    </div>

    <div class="p-5">
      <form method="POST" action="{{ route('admin.items.manual-store') }}" enctype="multipart/form-data"
            class="space-y-6" data-manual-form>
        @csrf

        {{-- Feedback / errors --}}
        <div data-manual-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
        <div data-manual-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

        <!-- Item Information -->
        <div class="bg-gray-50 shadow-md rounded-lg p-4">
          <div class="flex items-center mb-4">
            <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">1</div>
            <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <x-input-label for="manual_name" value="Item Name" />
              <x-text-input id="manual_name" name="name" type="text" class="mt-1 block w-full" required />
            </div>
            <div>
              <x-input-label for="manual_category" value="Category" />
              <select id="manual_category" name="category" class="mt-1 block w-full" required data-category-select></select>
            </div>
            <div>
              <x-input-label for="manual_quantity" value="Quantity (rows to render)" />
              <x-text-input id="manual_quantity" name="quantity" type="number" class="mt-1 block w-full" min="1" max="500" value="1" required data-manual-quantity />
            </div>
          </div>
        </div>

        <!-- Property Number Configuration & Manual Entry -->
        <div class="bg-gray-50 shadow-md rounded-lg p-4">
          <div class="flex items-center mb-4">
            <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">2</div>
            <h4 class="text-lg font-semibold text-gray-900">Property Number Configuration & Manual Entry</h4>
          </div>

                    <!-- config row (compact inputs) -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
              <x-input-label for="manual_year" value="Year" />
              <x-text-input id="manual_year" name="year_procured" type="text" maxlength="4" class="mt-1 block w-full" data-manual-config="year" />
            </div>

            <div>
              <x-input-label for="manual_ppe_display" value="PPE (read-only)" />
              <x-text-input id="manual_ppe_display" type="text" class="mt-1 block w-full bg-gray-100" readonly data-ppe-display />
              <input type="hidden" name="ppe_code" data-property-segment="ppe" />
            </div>

            <div>
              <x-input-label for="manual_serial_template" value="Serial template" />
              <x-text-input id="manual_serial_template" type="text" class="mt-1 block w-full" placeholder="e.g. 0001" data-manual-config="serial" />
            </div>

            <div>
              <x-input-label for="manual_office" value="Office code" />
              <x-text-input id="manual_office" name="office_code" type="text" maxlength="4" class="mt-1 block w-full" data-manual-config="office" />
            </div>
          </div>

          <!-- place this AFTER your config inputs (Year / PPE / Serial template / Office) -->
        <div class="mt-4">
        <div class="text-xs text-gray-600 mb-2">Manual Property Number Entry</div>

        <div id="manual_rows_container"
            class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white"
            aria-live="polite">
            {{-- JS will inject per-row panels here --}}
        </div>
        </div>


        <!-- Additional Details -->
        <div class="bg-gray-50 shadow-md rounded-lg p-4">
          <div class="flex items-center mb-4">
            <div class="bg-purple-100 text-purple-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">3</div>
            <h4 class="text-lg font-semibold text-gray-900">Additional Details</h4>
          </div>

          <div>
            <x-input-label for="manual_photo" value="Photo Upload" />
            <input id="manual_photo" name="photo" type="file" accept="image/*" data-filepond="true" />
          </div>
        </div>

        <div class="mt-4 border-t pt-4 flex justify-end gap-3">
          <x-button variant="secondary" type="button" data-manual-cancel x-on:click="$dispatch('close-modal','manual-property-entry')">Cancel</x-button>
          <x-button variant="primary" type="submit" data-manual-submit>Save</x-button>
        </div>
      </form>
    </div>
  </div>

  {{-- expose routes used by the module --}}
  <script>
    window.__validatePnsRoute = "{{ route('admin.items.validate-pns') }}";
    window.__manualStoreRoute = "{{ route('admin.items.manual-store') }}";
  </script>
</x-modal>
