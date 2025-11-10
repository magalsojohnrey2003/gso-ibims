@php
    // Build a map of category id => name for quick lookup (safe default if $categories empty)
    $categoryMap = collect($categories ?? [])->filter()->keyBy('id')->map(fn($c) => $c['name'])->toArray();
    $bootstrapCategories = collect($categories ?? [])->map(function ($c) {
        return [
            'id' => $c['id'] ?? null,
            'name' => $c['name'] ?? '',
            'category_code' => isset($c['category_code'])
                ? preg_replace('/\D+/', '', (string) $c['category_code'])
                : '',
        ];
    })->values()->toArray();
    $bootstrapOffices = collect($offices ?? [])->map(function ($o) {
        return [
            'code' => isset($o['code']) ? preg_replace('/\D+/', '', (string) $o['code']) : '',
            'name' => $o['name'] ?? '',
        ];
    })->values()->toArray();
    $bootstrapCategoryCodes = [];
    foreach ($categoryCodeMap ?? [] as $name => $codeValue) {
        $digits = preg_replace('/\D+/', '', (string) $codeValue);
        if ($name && $digits !== '') {
            $bootstrapCategoryCodes[$name] = str_pad(substr($digits, 0, 4), 4, '0', STR_PAD_LEFT);
        }
    }
    foreach ($categories ?? [] as $category) {
        $digits = isset($category['category_code']) ? preg_replace('/\D+/', '', (string) $category['category_code']) : '';
        if ($digits === '') {
            continue;
        }
        $code = str_pad(substr($digits, 0, 4), 4, '0', STR_PAD_LEFT);
        if (! empty($category['name'])) {
            $bootstrapCategoryCodes[$category['name']] = $code;
        }
        if (isset($category['id'])) {
            $bootstrapCategoryCodes[(string) $category['id']] = $code;
        }
    }
@endphp

@php
    $noMainScroll = false; // Enable main content scrolling since we removed table scrollbar
@endphp

<x-app-layout>
    <div
        class="hidden"
        data-items-bootstrap
        data-items-categories='@json($bootstrapCategories)'
        data-items-offices='@json($bootstrapOffices)'
        data-items-category-codes='@json($bootstrapCategoryCodes)'>
    </div>
    
    <!-- Title and Actions Section -->
    <div class="py-2">
        <div class="px-2">
            @if(session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif
            @if(session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif
            @if($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif
            
            <!-- Title Row with Search Bar - Contained Box -->
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2"
                                size="2xl"
                                weight="bold"
                                icon="archive-box"
                                variant="s"
                                iconStyle="plain"
                                iconColor="gov-accent"
                                compact="true"> Items Management </x-title>
                    </div>
                    
                    <!-- Live Search Bar -->
                    <div class="flex-shrink-0 relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        <input type="text"
                               id="items-live-search"
                               placeholder="Search by Name or Category"
                               class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="pb-2">
        <div class="px-2">
          <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
              <div class="table-container-no-scroll">
                  <table class="w-full text-sm text-center text-gray-600 gov-table">
                      <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                          <tr>
                              <th class="px-6 py-3">Photo</th>
                              <th class="px-6 py-3">Name</th>
                              <th class="px-6 py-3">Category</th>
                              <th class="px-6 py-3">Total Qty</th>
                              <th class="px-6 py-3">Available</th>
                              <th class="px-6 py-3">Actions</th>
                          </tr>
                      </thead>
                <tbody class="text-center">
                      @forelse ($items as $item)
                          @php
                              // Determine display name for category: if item->category is numeric id and map exists, use mapped name.
                              $displayCategory = $item->category;
                              if (is_numeric($item->category) && isset($categoryMap[(int)$item->category])) {
                                  $displayCategory = $categoryMap[(int)$item->category];
                              } else {
                                  // If it's a string, keep as-is (but avoid impossible nulls)
                                  $displayCategory = $displayCategory ?? '';
                              }
                          @endphp
                          <tr class="hover:bg-gray-50 text-center" data-item-row="{{ $item->id }}">
                              <td class="px-6 py-4" data-item-photo>
                                  @php
                                      $photoUrl = null;
                                      if ($item->photo) {
                                          // Check if photo is in storage (public disk)
                                          if (Storage::disk('public')->exists($item->photo)) {
                                              $photoUrl = Storage::disk('public')->url($item->photo);
                                          } 
                                          // Check if it's a full HTTP URL
                                          elseif (str_starts_with($item->photo, 'http')) {
                                              $photoUrl = $item->photo;
                                          } 
                                          // Check if it's in public directory (default photo or legacy path)
                                          elseif (file_exists(public_path($item->photo))) {
                                              $photoUrl = asset($item->photo);
                                          }
                                      }
                                      // Use default photo if no photo found or photo column is empty
                                      if (!$photoUrl) {
                                          $photoUrl = asset($defaultPhoto);
                                      }
                                  @endphp
                                  <div class="flex justify-center">
                                      <img src="{{ $photoUrl }}" data-item-photo-img class="h-12 w-12 object-cover rounded-lg shadow-sm" alt="{{ $item->name }}">
                                  </div>
                              </td>
                              <td class="px-6 py-4 font-semibold text-gray-900 text-center" data-item-name>{{ $item->name }}</td>
                              <td class="px-6 py-4 text-center" data-item-category>{{ $displayCategory }}</td>
                              <td class="px-6 py-4 text-center" data-item-total>{{ $item->total_qty }}</td>
                              <td class="px-6 py-4 text-center" data-item-available>
                                  <span class="{{ $item->available_qty > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                                      {{ $item->available_qty }}
                                  </span>
                              </td>
                              <td class="px-6 py-4 text-center">
                                  <div class="flex items-center justify-center gap-3">
                                      <x-button
                                          variant="primary"
                                          size="sm"
                                          class="h-10 w-10 !px-0 !py-0 rounded-full shadow-lg [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="pencil-square"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'edit-item-{{ $item->id }}')">
                                          Edit
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="eye"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'view-item-{{ $item->id }}')">
                                          View
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="printer"
                                          type="button"
                                          data-print-stickers
                                          data-print-route="{{ route('admin.items.stickers', $item) }}"
                                          data-print-default="{{ max(1, min(5, $item->instances->count())) }}"
                                          data-print-quantity="{{ max(1, $item->instances->count()) }}"
                                          data-print-item="{{ $item->name }}"
                                          data-print-description="{{ $item->description }}"
                                          data-print-acquisition="{{ optional($item->acquisition_date)->format('m/d/Y') }}"
                                          :disabled="$item->instances->isEmpty()">
                                          Print
                                      </x-button>
                                      <x-button
                                          variant="danger"
                                          size="sm"
                                          class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="trash"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $item->id }}')">
                                          Delete
                                      </x-button>
                                  </div>
                              </td>
                          </tr>
                          @push('item-modals')
                              <x-modal name="edit-item-{{ $item->id }}" maxWidth="2xl">
                                  <div class="w-full bg-white shadow-lg overflow-hidden flex flex-col max-h-[85vh]">
                                      <div class="bg-purple-600 text-white px-6 py-5 sticky top-0 z-20">
                                          <h3 class="text-2xl font-bold flex items-center">
                                              <i class="fas fa-pencil-alt mr-2"></i>
                                              EDIT ITEM
                                          </h3>
                                          <p class="text-purple-100 mt-2 text-sm leading-relaxed">Modify the Details of the Selected Inventory Item.</p>
                                      </div>
                                      <div class="flex-1 overflow-y-auto relative p-5">
                                          @include('admin.items.edit', [
                                              'item' => $item,
                                              'categories' => $categories,
                                              'categoryCodeMap' => $categoryCodeMap,
                                          ])
                                      </div>
                                  </div>
                              </x-modal>
                              <x-modal name="view-item-{{ $item->id }}" maxWidth="3xl">
                                  <div class="w-full bg-white shadow-xl overflow-hidden flex flex-col max-h-[85vh]">
                                      <div class="bg-indigo-600 text-white px-6 py-5 sticky top-0 z-20">
                                          <h3 class="text-2xl font-bold flex items-center">
                                              <i class="fas fa-eye mr-2"></i>
                                              ITEM OVERVIEW
                                          </h3>
                                          <p class="text-indigo-100 mt-2 text-sm leading-relaxed">
                                              Review the latest information for <strong>{{ $item->name }}</strong>.
                                          </p>
                                      </div>
                                      <div class="flex-1 overflow-y-auto p-6">
                                          @include('admin.items.view', [
                                              'item' => $item,
                                              'displayCategory' => $displayCategory,
                                              'categoryMap' => $categoryMap,
                                          ])
                                      </div>
                                      <div class="px-6 py-4 border-t bg-white sticky bottom-0 z-20 flex justify-end">
                                          <x-button
                                              variant="secondary"
                                              iconName="x-mark"
                                              type="button"
                                              x-on:click="$dispatch('close-modal', 'view-item-{{ $item->id }}')">
                                              Close
                                          </x-button>
                                      </div>
                                  </div>
                              </x-modal>
                              <x-modal name="confirm-delete-{{ $item->id }}">
                                  <div class="p-6">
                                      <h2 class="text-lg font-bold text-red-600">
                                          Delete <strong>{{ $item->name }}</strong>?
                                      </h2>
                                      <p class="mt-2 text-sm text-gray-600">This action cannot be undone.</p>
                                      <div class="mt-6 flex justify-end space-x-3">
                                          <x-button
                                              variant="secondary"
                                              iconName="x-mark"
                                              x-on:click="$dispatch('close-modal', 'confirm-delete-{{ $item->id }}')">
                                              Cancel
                                          </x-button>
                                          <form method="POST" action="{{ route('items.destroy', $item->id) }}">
                                              @csrf
                                              @method('DELETE')
                                              <x-button
                                                  variant="danger"
                                                  iconName="trash"
                                                  type="submit">Delete</x-button>
                                          </form>
                                      </div>
                                  </div>
                              </x-modal>
                          @endpush
                      @empty
                          <tr>
                              <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                  <x-status-badge type="warning" text="No items found" />
                              </td>
                          </tr>
                      @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
        </div>
    </div>
  
  <x-modal name="create-item" maxWidth="2xl">
      <div class="w-full shadow-lg overflow-hidden flex flex-col max-h-[85vh] bg-white">
      <div class="bg-purple-600 text-white px-6 py-5 sticky top-0 z-20">
          <h3 class="text-2xl font-bold flex items-center">
              <i class="fas fa-plus mr-2"></i>
              Add New Item
          </h3>
          <p class="text-purple-100 mt-2 text-sm leading-relaxed">
              Please provide the necessary details below to Add new Item.
          </p>
      </div>
          <div class="flex-1 overflow-y-auto relative p-5">
              @include('admin.items.create', ['categories' => $categories, 'categoryCodeMap' => $categoryCodeMap])
            
          </div>
      </div>
  </x-modal>
  
<!-- Floating Action Menu -->
<div x-data="{ open: false }" class="fixed bottom-8 right-8 z-50">
    <button
        @click="open = !open"
        :aria-expanded="open"
        type="button"
        class="relative rounded-full w-14 h-14 flex items-center justify-center shadow-lg focus:outline-none transition-all duration-300 transform"
        :class="open ? 'bg-red-600 hover:bg-red-700 scale-105' : 'bg-purple-600 hover:bg-purple-700 hover:scale-110'">
        <span aria-hidden="true"
            class="absolute inset-0 rounded-full opacity-0 transition-opacity duration-300"
            :class="open ? 'opacity-20 bg-black/10' : ''"></span>
        <svg xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            stroke="currentColor"
            fill="none"
            class="w-7 h-7 text-white transform transition-transform duration-300"
            :class="open ? 'rotate-45' : 'rotate-0'">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v16m8-8H4" />
        </svg>
        <span class="sr-only">Open actions</span>
    </button>
    <!-- Action cards -->
    <div
        x-show="open"
        x-transition.origin.bottom.right
        class="absolute bottom-20 right-0 flex flex-col gap-3 items-end"
        @click.outside="open = false">
        <!-- Add New Item -->
        <button
            x-on:click="$dispatch('open-modal', 'create-item'); open = false"
            class="group bg-white text-blue-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-blue-200 hover:border-blue-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
            <div class="bg-blue-100 p-2 rounded-lg group-hover:bg-blue-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <div class="text-left">
                <div class="font-semibold text-sm">Add Item</div>
                <div class="text-xs text-blue-500">Generate Property Numbers by Quantity</div>
            </div>
        </button>
        <!-- Manage Categories -->
        <button
            x-on:click="$dispatch('open-modal', 'manage-category'); open = false"
            class="group bg-white text-green-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-green-200 hover:border-green-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
            <div class="bg-green-100 p-2 rounded-lg group-hover:bg-green-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                </svg>
            </div>
            <div class="text-left">
                <div class="font-semibold text-sm">Category</div>
                <div class="text-xs text-green-500">Manage categories and PPE mapping</div>
            </div>
        </button>
        <!-- Manage Offices -->
        <button
            x-on:click="$dispatch('open-modal', 'manage-office'); open = false"
            class="group bg-white text-yellow-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-yellow-200 hover:border-yellow-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
            <div class="bg-yellow-100 p-2 rounded-lg group-hover:bg-yellow-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M9 8h6"/>
                </svg>
            </div>
            <div class="text-left">
                <div class="font-semibold text-sm">Office Code</div>
                <div class="text-xs text-yellow-500">Manage office codes</div>
            </div>
        </button>
</div>
</div>
@stack('item-modals')
@include('admin.items.modals.category')
@include('admin.items.modals.office')
<x-modal name="print-stickers" maxWidth="lg">
    <div class="p-6 space-y-6" data-print-modal>
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Print Stickers</h3>
            <p class="text-sm text-gray-500" data-print-summary></p>
        </div>
        <form class="space-y-5" data-print-form>
            <input type="hidden" data-print-route-input>
            <input type="hidden" data-print-quantity-input>
            <div class="space-y-2">
                <x-input-label for="print-person-accountable" value="Person Accountable" />
                <x-text-input
                    id="print-person-accountable"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="off"
                    data-print-person>
                </x-text-input>
                <p class="text-xs text-gray-500">Optional: include who will receive the assets.</p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Signature</span>
                    <button type="button" class="text-xs text-blue-600 hover:underline" data-print-signature-clear>Clear</button>
                </div>
                <div class="border border-gray-300 rounded-lg bg-white overflow-hidden">
                    <canvas data-print-signature-canvas class="w-full h-40 touch-pan-y"></canvas>
                </div>
                <p class="text-xs text-gray-500">Sign using your mouse, trackpad, or finger. Leave blank if a handwritten signature will be applied later.</p>
            </div>
            <div class="flex justify-end gap-3">
                <x-button
                    type="button"
                    variant="secondary"
                    data-print-cancel
                    iconName="x-mark">
                    Cancel
                </x-button>
                <x-button
                    type="submit"
                    iconName="printer"
                    data-print-submit>
                    Generate Stickers
                </x-button>
            </div>
        </form>
    </div>
</x-modal>

<script>
// Live search functionality for Items Management table
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('items-live-search');
    const table = document.querySelector('.gov-table tbody');
    
    if (!searchInput || !table) return;
    
    // Dynamic placeholder change on focus/blur
    searchInput.addEventListener('focus', function() {
        this.placeholder = 'Type to Search';
    });
    
    searchInput.addEventListener('blur', function() {
        this.placeholder = 'Search by Name or Category';
    });
    
    // Live search filtering
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tr[data-item-row]');
        
        rows.forEach(row => {
            // Get item name and category from the row
            const nameCell = row.querySelector('[data-item-name]');
            const categoryCell = row.querySelector('[data-item-category]');
            
            if (!nameCell || !categoryCell) return;
            
            const itemName = nameCell.textContent.toLowerCase();
            const itemCategory = categoryCell.textContent.toLowerCase();
            
            // Check if search term matches name or category
            const matches = itemName.includes(searchTerm) || itemCategory.includes(searchTerm);
            
            // Show/hide row based on match
            if (matches) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Check if any rows are visible
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        
        // Handle empty state
        const emptyRow = table.querySelector('tr:not([data-item-row])');
        if (visibleRows.length === 0 && !emptyRow) {
            // Add "no results" message if all filtered out
            const noResultsRow = document.createElement('tr');
            noResultsRow.id = 'no-results-row';
            noResultsRow.innerHTML = `
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-medium">No items found</p>
                        <p class="text-sm">Try adjusting your search</p>
                    </div>
                </td>
            `;
            table.appendChild(noResultsRow);
        } else if (visibleRows.length > 0) {
            // Remove "no results" message if items are visible
            const noResultsRow = document.getElementById('no-results-row');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    });
});
</script>

</x-app-layout>
@vite(['resources/js/app.js'])