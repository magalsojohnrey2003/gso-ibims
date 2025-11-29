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
                                iconColor="title-purple"
                                compact="true"> Items Management </x-title>
                    </div>
                    
                    <!-- Live Search Bar + Borrowable Filter -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full md:w-auto">
                        <div class="flex-shrink-0 relative w-full sm:w-64">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text"
                                   id="items-live-search"
                                   placeholder="Search by Name or Category"
                                   class="gov-input pl-12 pr-4 py-2.5 text-sm w-full transition duration-200 focus:outline-none focus:ring-0" />
                        </div>
                        <div class="flex-shrink-0 w-full sm:w-52">
                            <label for="items-borrowable-filter" class="sr-only">Catalog visibility filter</label>
                            <div class="relative">
                                <select
                                    id="items-borrowable-filter"
                                    class="gov-input pr-10 py-2.5 text-sm w-full appearance-none focus:outline-none focus:ring-0"
                                    data-items-borrowable-filter>
                                    <option value="all" selected>All Items</option>
                                    <option value="borrowable">Borrowable Only</option>
                                    <option value="hidden">Hidden from Catalog</option>
                                </select>
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </span>
                            </div>
                        </div>
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
                  <table class="w-full text-sm text-center text-gray-600 gov-table" data-items-table>
                      <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                          <tr>
                              <th class="px-6 py-3">Photo</th>
                              <th class="px-6 py-3">
                                  <button
                                      type="button"
                                      class="inline-flex items-center justify-center gap-1 w-full uppercase tracking-wide text-xs font-semibold text-white/80 transition-opacity hover:text-white focus:outline-none"
                                      data-items-sort-key="name"
                                      data-sort-type="string">
                                      <span>Name</span>
                                      <span class="text-[10px] opacity-0 transition-opacity" aria-hidden="true" data-sort-indicator>▲</span>
                                  </button>
                              </th>
                              <th class="px-6 py-3">
                                  <button
                                      type="button"
                                      class="inline-flex items-center justify-center gap-1 w-full uppercase tracking-wide text-xs font-semibold text-white/80 transition-opacity hover:text-white focus:outline-none"
                                      data-items-sort-key="category"
                                      data-sort-type="string">
                                      <span>Category</span>
                                      <span class="text-[10px] opacity-0 transition-opacity" aria-hidden="true" data-sort-indicator>▲</span>
                                  </button>
                              </th>
                              <th class="px-6 py-3">
                                  <button
                                      type="button"
                                      class="inline-flex items-center justify-center gap-1 w-full uppercase tracking-wide text-xs font-semibold text-white/80 transition-opacity hover:text-white focus:outline-none"
                                      data-items-sort-key="total"
                                      data-sort-type="number">
                                      <span>Total Qty</span>
                                      <span class="text-[10px] opacity-0 transition-opacity" aria-hidden="true" data-sort-indicator>▲</span>
                                  </button>
                              </th>
                              <th class="px-6 py-3">
                                  <button
                                      type="button"
                                      class="inline-flex items-center justify-center gap-1 w-full uppercase tracking-wide text-xs font-semibold text-white/80 transition-opacity hover:text-white focus:outline-none"
                                      data-items-sort-key="available"
                                      data-sort-type="number">
                                      <span>Available</span>
                                      <span class="text-[10px] opacity-0 transition-opacity" aria-hidden="true" data-sort-indicator>▲</span>
                                  </button>
                              </th>
                              <th class="px-6 py-3">Actions</th>
                          </tr>
                      </thead>
                <tbody id="itemsTableBody" class="text-center">
                      <x-table-loading-state colspan="6" data-items-loading-row />
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
                          <tr class="hover:bg-gray-50 text-center" data-item-row="{{ $item->id }}" data-item-borrowable="{{ $item->is_borrowable ? '1' : '0' }}">
                              <td class="px-6 py-4" data-item-photo>
                                  <div class="flex justify-center">
                                      <img src="{{ $item->photo_url }}" data-item-photo-img class="h-12 w-12 object-cover rounded-lg shadow-sm" alt="{{ $item->name }}">
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
                                          variant="secondary"
                                          size="sm"
                                          class="btn-action btn-utility btn-edit h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="pencil-square"
                                          title="Edit item"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'edit-item-{{ $item->id }}')">
                                          Edit
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="btn-action btn-view h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="eye"
                                          title="View item details"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'view-item-{{ $item->id }}')">
                                          View
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="btn-action btn-print h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="printer"
                                          type="button"
                                          data-print-stickers
                                          data-print-route="{{ route('admin.items.stickers', $item) }}"
                                          data-print-default="{{ max(1, min(5, $item->instances->count())) }}"
                                          data-print-quantity="{{ max(1, $item->instances->count()) }}"
                                          data-print-item="{{ $item->name }}"
                                          data-print-description="{{ $item->description }}"
                                          data-print-acquisition="{{ optional($item->acquisition_date)->format('M. j, Y') }}"
                                          title="Print property stickers"
                                          :disabled="$item->instances->isEmpty()">
                                          Print
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="btn-action btn-delete h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="trash"
                                          title="Delete item"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $item->id }}')">
                                          Delete
                                      </x-button>
                                  </div>
                              </td>
                          </tr>
                          @push('item-modals')
                              <x-modal name="edit-item-{{ $item->id }}" maxWidth="2xl">
                                  <div class="w-full bg-white dark:bg-gray-900 shadow-lg overflow-hidden flex flex-col max-h-[85vh]">
                                      <div class="bg-purple-600 text-white px-6 py-5 sticky top-0 z-20 relative">
                                          <button 
                                              type="button"
                                              data-modal-close-button
                                              data-modal-name="edit-item-{{ $item->id }}"
                                              x-on:click="$dispatch('close-modal', 'edit-item-{{ $item->id }}')"
                                              class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors p-2 hover:bg-white/10 rounded-lg">
                                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                              </svg>
                                          </button>
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
                          <x-table-empty-state colspan="6" data-items-empty-row class="hidden" />
                      @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
        </div>
    </div>
  
  <x-modal name="create-item" maxWidth="2xl">
      <div class="w-full shadow-lg overflow-hidden flex flex-col max-h-[85vh] bg-white dark:bg-gray-900">
      <div class="bg-purple-600 text-white px-6 py-5 sticky top-0 z-20 relative">
          <button 
              type="button"
              x-on:click="(() => { const f = document.getElementById('create-item-form'); if (f) { f.reset(); f.dispatchEvent(new Event('reset')); } })(); $dispatch('close-modal', 'create-item')"
              class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors p-2 hover:bg-white/10 rounded-lg">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
          </button>
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
                <i class="fas fa-tags text-lg"></i>
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
                <i class="fas fa-building text-lg"></i>
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
@include('admin.items.modals.gla')
@include('admin.items.modals.office')
@include('admin.items.modals.history')
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
// Live search and sortable table for Items Management
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('itemsTableBody');
    const tableElement = document.querySelector('[data-items-table]');
    const searchInput = document.getElementById('items-live-search');
    const sortControls = Array.from(document.querySelectorAll('[data-items-sort-key]'));
    const borrowableFilter = document.querySelector('[data-items-borrowable-filter]');

    if (!tableBody || !tableElement) {
        return;
    }

    const loadingRow = tableBody.querySelector('[data-items-loading-row]');
    const templateEmptyRow = tableBody.querySelector('[data-items-empty-row]');
    const noResultsRowId = 'items-no-results-row';

    let nextOriginalIndex = 0;

    const assignOriginalIndex = (row) => {
        if (!row.dataset.originalIndex) {
            row.dataset.originalIndex = String(nextOriginalIndex);
            nextOriginalIndex += 1;
        }
    };

    Array.from(tableBody.querySelectorAll('tr[data-item-row]')).forEach((row) => {
        assignOriginalIndex(row);
    });

    const state = {
        search: searchInput ? searchInput.value.trim().toLowerCase() : '',
        sortKey: null,
        sortDirection: 'asc',
        filterBorrowable: borrowableFilter ? borrowableFilter.value : 'all',
    };

    const hideLoadingState = () => {
        if (loadingRow) {
            loadingRow.classList.add('hidden');
        }
        if (templateEmptyRow && nextOriginalIndex === 0) {
            templateEmptyRow.classList.remove('hidden');
            templateEmptyRow.style.display = '';
        }
    };

    if (document.readyState === 'complete') {
        hideLoadingState();
    } else {
        window.addEventListener('load', hideLoadingState, { once: true });
        setTimeout(hideLoadingState, 1200);
    }

    const parseNumber = (value) => {
        if (!value) {
            return 0;
        }
        const cleaned = value.replace(/[^0-9.-]/g, '');
        return cleaned === '' ? 0 : Number(cleaned);
    };

    const buildEntry = (row) => {
        const nameCell = row.querySelector('[data-item-name]');
        const categoryCell = row.querySelector('[data-item-category]');
        const totalCell = row.querySelector('[data-item-total]');
        const availableCell = row.querySelector('[data-item-available]');
        const availableSpan = availableCell ? availableCell.querySelector('span') : null;

        const name = nameCell ? nameCell.textContent.trim() : '';
        const category = categoryCell ? categoryCell.textContent.trim() : '';
        const total = totalCell ? parseNumber(totalCell.textContent) : 0;
        const available = availableSpan
            ? parseNumber(availableSpan.textContent)
            : (availableCell ? parseNumber(availableCell.textContent) : 0);

        const isBorrowable = row.dataset.itemBorrowable === '1' || row.dataset.itemBorrowable === 'true';

        return {
            row,
            name,
            nameLower: name.toLowerCase(),
            category,
            categoryLower: category.toLowerCase(),
            total,
            available,
            originalIndex: Number(row.dataset.originalIndex || 0),
            isBorrowable,
        };
    };

    const collectEntries = () => {
        const rows = Array.from(tableBody.querySelectorAll('tr[data-item-row]'));
        rows.forEach(assignOriginalIndex);
        return rows.map(buildEntry);
    };

    const matchesSearch = (entry) => {
        if (!state.search) {
            return true;
        }
        return entry.nameLower.includes(state.search) || entry.categoryLower.includes(state.search);
    };

    const matchesBorrowable = (entry) => {
        if (!borrowableFilter) {
            return true;
        }

        if (state.filterBorrowable === 'borrowable') {
            return entry.isBorrowable;
        }

        if (state.filterBorrowable === 'hidden') {
            return !entry.isBorrowable;
        }

        return true;
    };

    const compareEntries = (a, b) => {
        if (!state.sortKey) {
            return a.originalIndex - b.originalIndex;
        }

        const direction = state.sortDirection === 'asc' ? 1 : -1;

        if (state.sortKey === 'name') {
            const comparison = a.name.localeCompare(b.name, undefined, { sensitivity: 'base' });
            return comparison === 0 ? a.originalIndex - b.originalIndex : direction * comparison;
        }

        if (state.sortKey === 'category') {
            const comparison = a.category.localeCompare(b.category, undefined, { sensitivity: 'base' });
            return comparison === 0 ? a.originalIndex - b.originalIndex : direction * comparison;
        }

        if (state.sortKey === 'total') {
            if (a.total === b.total) {
                return a.originalIndex - b.originalIndex;
            }
            return direction * (a.total - b.total);
        }

        if (state.sortKey === 'available') {
            if (a.available === b.available) {
                return a.originalIndex - b.originalIndex;
            }
            return direction * (a.available - b.available);
        }

        return a.originalIndex - b.originalIndex;
    };

    const removeNoResultsRow = () => {
        const existing = document.getElementById(noResultsRowId);
        if (existing) {
            existing.remove();
        }
    };

    const getNoResultsRow = () => {
        let row = document.getElementById(noResultsRowId);
        if (!row) {
            row = document.createElement('tr');
            row.id = noResultsRowId;
            row.innerHTML = `
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-medium">No items found</p>
                        <p class="text-sm">Try adjusting your search or filter</p>
                    </div>
                </td>
            `;
        }
        return row;
    };

    const updateEmptyState = (visibleCount, totalRows) => {
        if (templateEmptyRow) {
            if (totalRows === 0) {
                templateEmptyRow.classList.remove('hidden');
                templateEmptyRow.style.display = '';
            } else {
                templateEmptyRow.classList.add('hidden');
                templateEmptyRow.style.display = 'none';
            }
        }

        if (totalRows === 0) {
            removeNoResultsRow();
            return;
        }

        if (visibleCount === 0) {
            const row = getNoResultsRow();
            tableBody.appendChild(row);
        } else {
            removeNoResultsRow();
        }
    };

    const updateSortIndicators = () => {
        sortControls.forEach((control) => {
            const indicator = control.querySelector('[data-sort-indicator]');
            const isActive = control.dataset.itemsSortKey === state.sortKey;
            control.classList.toggle('text-white', isActive);
            control.classList.toggle('text-white/80', !isActive);
            if (indicator) {
                if (isActive) {
                    indicator.textContent = state.sortDirection === 'asc' ? '▲' : '▼';
                    indicator.classList.remove('opacity-0');
                } else {
                    indicator.classList.add('opacity-0');
                }
            }
            const headerCell = control.closest('th');
            if (headerCell) {
                headerCell.setAttribute('aria-sort', isActive ? (state.sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
            }
        });
    };

    const applyFiltersAndSort = () => {
        const entries = collectEntries();
        const fragment = document.createDocumentFragment();
        let visibleCount = 0;

        entries.sort(compareEntries);

        entries.forEach((entry) => {
            const isVisible = matchesSearch(entry) && matchesBorrowable(entry);
            entry.row.style.display = isVisible ? '' : 'none';
            if (isVisible) {
                visibleCount += 1;
            }
            fragment.appendChild(entry.row);
        });

        tableBody.appendChild(fragment);
        updateEmptyState(visibleCount, entries.length);
        updateSortIndicators();
    };

    if (searchInput) {
        searchInput.addEventListener('focus', function () {
            this.placeholder = 'Type to Search';
        });

        searchInput.addEventListener('blur', function () {
            this.placeholder = 'Search by Name or Category';
        });

        searchInput.addEventListener('input', (event) => {
            state.search = event.target.value.trim().toLowerCase();
            applyFiltersAndSort();
        });
    }

    sortControls.forEach((control) => {
        const headerCell = control.closest('th');
        if (headerCell) {
            headerCell.setAttribute('aria-sort', 'none');
        }

        control.addEventListener('click', () => {
            const key = control.dataset.itemsSortKey;
            if (!key) {
                return;
            }

            if (state.sortKey === key) {
                if (state.sortDirection === 'asc') {
                    state.sortDirection = 'desc';
                } else {
                    state.sortKey = null;
                    state.sortDirection = 'asc';
                }
            } else {
                state.sortKey = key;
                state.sortDirection = 'asc';
            }

            applyFiltersAndSort();
        });
    });

    if (borrowableFilter) {
        borrowableFilter.addEventListener('change', (event) => {
            state.filterBorrowable = event.target.value;
            applyFiltersAndSort();
        });
    }

    applyFiltersAndSort();
});
</script>

</x-app-layout>
@vite(['resources/js/app.js'])
