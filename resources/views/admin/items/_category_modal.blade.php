{{-- resources/views/admin/items/_category_modal.blade.php --}}
@once
  @push('styles')
    {{-- optional styles if needed --}}
  @endpush
@endonce

<x-modal name="manage-ppe" maxWidth="md">
  <div class="p-6">
    <h3 class="text-lg font-bold mb-2">Manage PPE Codes</h3>
    <p class="text-sm text-gray-600 mb-4">Create or edit a category and its PPE code (2 digits).</p>

    <form id="ppeForm" class="space-y-3" onsubmit="return false;">
      <input type="hidden" id="ppe_mode" value="add"> <!-- 'add' or 'edit' -->
      <input type="hidden" id="ppe_edit_key" value="">

      <div class="grid grid-cols-2 gap-3">
        <div>
          <x-input-label for="category_name" value="Category name" />
          <x-text-input id="category_name" type="text" name="name" class="mt-1 block w-full" />
          <p class="text-xs text-red-600 hidden mt-1" id="category_name_error"></p>
        </div>
        <div>
          <x-input-label for="category_ppe" value="PPE code (2 digits)" />
          <x-text-input id="category_ppe" type="text" name="ppe" maxlength="2" class="mt-1 block w-full" inputmode="numeric" />
          <p class="text-xs text-red-600 hidden mt-1" id="category_ppe_error"></p>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <x-button variant="secondary" type="button" x-on:click="$dispatch('close-modal','manage-ppe')">Cancel</x-button>
        <x-button id="savePpeBtn" type="button">Save</x-button>
      </div>
    </form>

    <div class="mt-6">
      <h4 class="font-semibold mb-2">Current PPE Codes</h4>
      <div class="overflow-y-auto max-h-72 border rounded-lg bg-white">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-50 text-xs uppercase text-gray-600">
            <tr>
              <th class="px-4 py-2">Category</th>
              <th class="px-4 py-2">PPE Code</th>
              <th class="px-4 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="ppeTableBody" class="divide-y"></tbody>
        </table>
      </div>
    </div>
  </div>
</x-modal>

{{-- Provide globals expected by the JS file --}}
<script>
  // server-provided map (index() already merges storage file) - keep this in your main view too if you prefer
  window.__serverCategoryPpeMap = @json($categoryPpeMap ?? (isset($categoryPpeMap) ? $categoryPpeMap : []));
  // route for saving (item.ppe.save route)
  window.__savePpeRoute = "{{ route('items.categories.store')}}";
</script>

{{-- Include the compiled JS (see instructions below). --}}
{{-- If you are using Vite: import the JS inside resources/js/app.js instead (see instructions). --}}
{{-- If you want to include directly via asset (not recommended in production), use: --}}
{{-- <script src="{{ asset('js/item-category-modal.js') }}"></script> --}}
