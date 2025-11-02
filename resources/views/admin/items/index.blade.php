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
<x-app-layout>
    <x-title level="h2"
                size="2xl"
                weight="bold"
                icon="archive-box"
                variant="s"
                iconStyle="plain"
                iconColor="gov-accent"> Items Management </x-title>
    <div
        class="hidden"
        data-items-bootstrap
        data-items-categories='@json($bootstrapCategories)'
        data-items-offices='@json($bootstrapOffices)'
        data-items-category-codes='@json($bootstrapCategoryCodes)'>
    </div>
  <div class="py-6">
      <div class="sm:px-6 lg:px-8 space-y-10">
          @if(session('success'))
              <x-alert type="success" :message="session('success')" />
          @endif
          @if(session('error'))
              <x-alert type="error" :message="session('error')" />
          @endif
          @if($errors->any())
              <x-alert type="error" :message="$errors->first()" />
          @endif
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-4 rounded-xl shadow-md border border-purple-100">
              <form method="GET" action="{{ route('items.index') }}" class="flex items-center gap-3 flex-1">
                  <div class="relative flex-1 max-w-md">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                          <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                          </svg>
                      </div>
                      <input type="text"
                             name="search"
                             value="{{ request('search') }}"
                             placeholder="Search by name or category..."
                             class="block w-full pl-10 pr-3 py-3 border border-purple-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm placeholder-gray-400" />
                  </div>
                  <x-button
                      variant="primary"
                      iconName="magnifying-glass"
                      type="submit"
                      class="text-sm px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                      Search
                  </x-button>
              </form>
          </div>
          <div class="overflow-x-auto rounded-2xl shadow-xl border border-purple-100 bg-white">
              <table class="w-full text-sm text-center text-gray-700">
                  <thead class="bg-gradient-to-r from-purple-600 to-purple-700 text-white text-xs uppercase font-bold tracking-wider">
                      <tr>
                          <th class="px-6 py-4 first:rounded-tl-2xl">Photo</th>
                          <th class="px-6 py-4">Name</th>
                          <th class="px-6 py-4">Category</th>
                          <th class="px-6 py-4">Total Qty</th>
                          <th class="px-6 py-4">Available</th>
                          <th class="px-6 py-4 last:rounded-tr-2xl">Actions</th>
                      </tr>
                  </thead>
            <tbody class="bg-white divide-y divide-purple-50">
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
                          <tr class="hover:bg-purple-50 transition-all duration-200 border-b border-purple-50" data-item-row="{{ $item->id }}">
                              <td class="px-6 py-5 text-center" data-item-photo>
                                  @if($item->photo)
                                      @php
                                          $photoUrl = Storage::disk('public')->exists($item->photo)
                                              ? Storage::disk('public')->url($item->photo)
                                              : asset($item->photo);
                                      @endphp
                                      <div class="flex justify-center">
                                          <img src="{{ $photoUrl }}" data-item-photo-img class="h-16 w-16 object-cover rounded-xl shadow-md ring-2 ring-purple-100 hover:ring-purple-300 transition-all duration-200">
                                      </div>
                                  @else
                                      <div class="flex justify-center">
                                          <div class="h-16 w-16 rounded-xl bg-purple-100 flex items-center justify-center">
                                              <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                              </svg>
                                          </div>
                                      </div>
                                  @endif
                              </td>
                              <td class="px-6 py-5 font-bold text-gray-900 text-base" data-item-name>{{ $item->name }}</td>
                              <td class="px-6 py-5" data-item-category>
                                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-700 border border-purple-200">
                                      {{ $displayCategory }}
                                  </span>
                              </td>
                              <td class="px-6 py-5" data-item-total>
                                  <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-700 font-bold text-sm">
                                      {{ $item->total_qty }}
                                  </span>
                              </td>
                              <td class="px-6 py-5" data-item-available>
                                  <span class="inline-flex items-center justify-center px-4 py-2 rounded-lg font-bold text-sm {{ $item->available_qty > 0 ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' }}">
                                      {{ $item->available_qty }}
                                  </span>
                              </td>
                              <td class="px-6 py-5">
                                  <div class="flex items-center justify-center gap-2">
                                      <x-button
                                          variant="primary"
                                          size="sm"
                                          class="h-11 w-11 !px-0 !py-0 rounded-xl shadow-md hover:shadow-lg hover:scale-110 transition-all duration-200 bg-purple-600 hover:bg-purple-700 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="pencil-square"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'edit-item-{{ $item->id }}')">
                                          Edit
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="h-11 w-11 !px-0 !py-0 rounded-xl shadow-md hover:shadow-lg hover:scale-110 transition-all duration-200 bg-indigo-600 hover:bg-indigo-700 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                                          iconName="eye"
                                          x-data
                                          x-on:click.prevent="$dispatch('open-modal', 'view-item-{{ $item->id }}')">
                                          View
                                      </x-button>
                                      <x-button
                                          variant="secondary"
                                          size="sm"
                                          class="h-11 w-11 !px-0 !py-0 rounded-xl shadow-md hover:shadow-lg hover:scale-110 transition-all duration-200 bg-blue-600 hover:bg-blue-700 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
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
                                          class="h-11 w-11 !px-0 !py-0 rounded-xl shadow-md hover:shadow-lg hover:scale-110 transition-all duration-200 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
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
                              <td colspan="6" class="px-6 py-16 text-center">
                                  <div class="flex flex-col items-center justify-center space-y-4">
                                      <div class="w-20 h-20 rounded-full bg-purple-100 flex items-center justify-center">
                                          <svg class="w-10 h-10 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                          </svg>
                                      </div>
                                      <div>
                                          <p class="text-lg font-semibold text-gray-700">No items found</p>
                                          <p class="text-sm text-gray-500 mt-1">Try adjusting your search criteria</p>
                                      </div>
                                  </div>
                              </td>
                          </tr>
                      @endforelse
                  </tbody>
              </table>
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
</x-app-layout>
@vite(['resources/js/app.js'])