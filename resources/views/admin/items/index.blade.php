@php
    // Build a map of category id => name for quick lookup (safe default if $categories empty)
    $categoryMap = collect($categories ?? [])->filter()->keyBy('id')->map(fn($c) => $c['name'])->toArray();
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
        data-items-categories='@json(collect($categories)->map(function($c){
            return ['id' => $c['id'] ?? null, 'name' => $c['name'] ?? ''];
        })->values())'
        data-items-offices='@json($offices ?? [])'
        data-items-category-codes='@json($categoryCodeMap ?? [])'>
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

          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
              <form method="GET" action="{{ route('items.index') }}" class="flex items-center gap-2">
                  <input type="text"
                         name="search"
                         value="{{ request('search') }}"
                         placeholder="Search by name or category..."
                         class="border rounded-lg px-3 py-2 text-sm w-64 focus:ring focus:ring-blue-200" />

                  <x-button
                      variant="secondary"
                      iconName="magnifying-glass"
                      type="submit"
                      class="text-sm px-3 py-2">
                      Search
                  </x-button>
              </form>

          </div>

          <div class="overflow-x-auto rounded-2xl shadow-lg">
              <table class="w-full text-sm text-left text-gray-600 shadow-sm border rounded-lg overflow-hidden">
                  <thead class="bg-purple-600 text-white text-xs uppercase font-semibold">
                      <tr>
                          <th class="px-6 py-3">Photo</th>
                          <th class="px-6 py-3">Name</th>
                          <th class="px-6 py-3">Category</th>
                          <th class="px-6 py-3 text-center ">Total Qty</th>
                          <th class="px-6 py-3 text- center">Available</th>
                          <th class="px-6 py-3 text-right">Actions</th>
                      </tr>
                  </thead>
            <tbody class="divide-y bg-white">
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
                          <tr class="hover:bg-gray-50" data-item-row="{{ $item->id }}">
                              <td class="px-6 py-4" data-item-photo>
                              @if($item->photo)
                                  @php
                                      $photoUrl = Storage::disk('public')->exists($item->photo)
                                          ? Storage::disk('public')->url($item->photo)
                                          : asset($item->photo);
                                  @endphp

                                  <img src="{{ $photoUrl }}" data-item-photo-img class="h-12 w-12 object-cover rounded-lg shadow-sm">
                              @else
                                  <x-status-badge type="gray" text="No photo" />
                              @endif

                              </td>

                              <td class="px-6 py-4 font-medium" data-item-name>{{ $item->name }}</td>
                              <td class="px-6 py-4" data-item-category>{{ $displayCategory }}</td>
                              <td class="px-6 py-4 text-center" data-item-total>{{ $item->total_qty }}</td>
                              <td class="px-6 py-4 text-center" data-item-available>
                                  <span class="{{ $item->available_qty > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                                      {{ $item->available_qty }}
                                  </span>
                              </td>

                              <td class="px-6 py-4 text-right space-x-2">

                                  <x-button
                                      variant="primary"
                                      iconName="pencil-square"
                                      x-data
                                      x-on:click.prevent="$dispatch('open-modal', 'edit-item-{{ $item->id }}')">
                                      Edit
                                  </x-button>
                                  <x-button
                                      variant="danger"
                                      iconName="trash"
                                      x-data
                                      x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $item->id }}')">
                                      Delete
                                  </x-button>
                              </td>
                          </tr>
                          <x-modal name="edit-item-{{ $item->id }}" maxWidth="2xl">
                              <div class="w-full bg-white shadow-lg overflow-hidden">
                                  <div class="bg-purple-600 text-white px-6 py-5">
                                      <h3 class="text-2xl font-bold flex items-center">
                                          <i class="fas fa-pencil-alt mr-2"></i>
                                          EDIT ITEM
                                      </h3>
                                      <p class="text-purple-100 mt-2 text-sm leading-relaxed">Modify the Details of the Selected Inventory Item.</p>
                                  </div>

                                  <div class="p-5">
                                      @include('admin.items.edit', [
                                          'item' => $item,
                                          'categories' => $categories,
                                          'categoryCodeMap' => $categoryCodeMap,
                                      ])
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
  
  <x-modal name="create-item" maxWidth="2xl">
      <div class="w-full shadow-lg overflow-hidden">

      <div class="bg-purple-600 text-white px-6 py-5">
          <h3 class="text-2xl font-bold flex items-center">
              <i class="fas fa-plus mr-2"></i>
              Add New Item
          </h3>
          <p class="text-purple-100 mt-2 text-sm leading-relaxed">
              Please provide the necessary details below to Add new Item.
          </p>
      </div>

          <div class="p-5">
              @include('admin.items.create', ['categories' => $categories, 'categoryCodeMap' => $categoryCodeMap])
            
          </div>
          
      </div>
  </x-modal>

</x-app-layout>

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

@include('admin.items.modals')

@vite(['resources/js/app.js'])
