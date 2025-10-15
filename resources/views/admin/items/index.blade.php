<x-app-layout>
    <x-title level="h2"
                size="2xl"
                weight="bold"
                icon="archive-box"
                variant="s"
                iconStyle="plain"
                iconColor="gov-accent"> Items Management </x-title>
    <script>
        window.__serverCategories = @json($categories ?? []);
    </script>

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
                            <th class="px-6 py-3">Total Qty</th>
                            <th class="px-6 py-3">Available</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-white">
                        @forelse ($items as $item)
                            <tr class="hover:bg-gray-50" data-item-row="{{ $item->id }}">
                                <td class="px-6 py-4" data-item-photo>
                                @if($item->photo)
                                    @php
                                        // Prefer Storage public disk if the file exists there,
                                        // otherwise fall back to a public path (e.g. public/images/item.png).
                                        $photoUrl = Storage::disk('public')->exists($item->photo)
                                            ? Storage::disk('public')->url($item->photo)  // -> /storage/...
                                            : asset($item->photo);                        // -> /images/...
                                    @endphp

                                    <img src="{{ $photoUrl }}" data-item-photo-img class="h-12 w-12 object-cover rounded-lg shadow-sm">
                                @else
                                    <x-status-badge type="gray" text="No photo" />
                                @endif

                                </td>

                                <td class="px-6 py-4 font-medium" data-item-name>{{ $item->name }}</td>
                                <td class="px-6 py-4" data-item-category>{{ ucfirst($item->category) }}</td>
                                <td class="px-6 py-4 text-right" data-item-total>{{ $item->total_qty }}</td>
                                <td class="px-6 py-4 text-right" data-item-available>
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
                                <!-- Full modal container with purple header touching the top -->
                                <div class="w-full bg-white shadow-lg overflow-hidden">

                                  <!-- Purple header that fully touches the top edge -->
                                    <div class="bg-purple-600 text-white px-6 py-5">
                                        <h3 class="text-2xl font-bold flex items-center">
                                            <!-- FontAwesome Edit (Pencil) Icon -->
                                            <i class="fas fa-pencil-alt mr-2"></i>
                                            EDIT ITEM
                                        </h3>
                                        <p class="text-purple-100 mt-2 text-sm leading-relaxed">Modify the Details of the Selected Inventory Item.</p>
                                    </div>

                                    <!-- Content body -->
                                    <div class="p-5">
                                        @include('admin.items.edit', [
                                            'item' => $item,
                                            'categories' => $categories,
                                            'categoryPpeMap' => $categoryPpeMap,
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

            <!-- Content body -->
            <div class="p-5">
                @include('admin.items.create', ['categories' => $categories, 'categoryPpeMap' => $categoryPpeMap])
              
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

        <!-- circle pulse / subtle ring on open -->
        <span aria-hidden="true"
            class="absolute inset-0 rounded-full opacity-0 transition-opacity duration-300"
            :class="open ? 'opacity-20 bg-black/10' : ''"></span>

        <!-- Plus SVG that rotates into an X (rotate 45deg -> X) -->
        <svg xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            stroke="currentColor"
            fill="none"
            class="w-7 h-7 text-white transform transition-transform duration-300"
            :class="open ? 'rotate-45' : 'rotate-0'">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v16m8-8H4" />
        </svg>

        <!-- Optional visually-hidden label -->
        <span class="sr-only">Open actions</span>
    </button>

    <!-- Action cards -->
    <div
        x-show="open"
        x-transition.origin.bottom.right
        class="absolute bottom-20 right-0 flex flex-col gap-3 items-end"
        @click.outside="open = false">

      
        <!-- Manual Property Number Entry -->
        <button
            x-on:click="$dispatch('open-modal','manual-property-entry'); open = false"
            class="group bg-white text-emerald-700 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-emerald-200 hover:border-emerald-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
            <div class="bg-emerald-100 p-2 rounded-lg group-hover:bg-emerald-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 7h6l3-3 3 3h6v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                </svg>
            </div>
            <div class="text-left">
                <div class="font-semibold text-sm">Manual PN Entry</div>
                <div class="text-xs text-emerald-500">Enter property numbers manually</div>
            </div>
        </button>

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
                <div class="font-semibold text-sm">Add New Item</div>
                <div class="text-xs text-blue-500">Add item to inventory</div>
            </div>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Listen for successful edits and update the row in-place
  document.addEventListener('items:edit:success', (e) => {
    try {
      const data = e.detail || {};
      const id = data.item_id ?? data.id;
      if (!id) return;

      // Find row by data attribute
      const row = document.querySelector(`[data-item-row="${id}"]`);
      if (!row) return;

      // Helper to safely set text
      const setText = (selector, text) => {
        const el = row.querySelector(selector);
        if (!el) return;
        // if element contains a <span> for styling (like available cell), update its textContent
        const target = el.querySelector('span') || el;
        target.textContent = String(text ?? '');
      };

      // Name
      if (typeof data.name === 'string') setText('[data-item-name]', data.name);

      // Category (if returned)
      if (typeof data.category === 'string') setText('[data-item-category]', data.category.charAt(0).toUpperCase() + data.category.slice(1));

      // Total qty and available qty (if returned)
      if (typeof data.total_qty !== 'undefined') setText('[data-item-total]', data.total_qty);
      if (typeof data.available_qty !== 'undefined') setText('[data-item-available]', data.available_qty);

      // Office and property number (if you included hidden cells)
      if (typeof data.office_code === 'string') setText('[data-item-office]', data.office_code);
      if (typeof data.property_number === 'string') setText('[data-item-pn]', data.property_number);

      // Photo update (if server returned full URL)
      if (typeof data.photo === 'string' && data.photo) {
        const img = row.querySelector('[data-item-photo-img]');
        if (img) {
          img.src = data.photo;
        }
      }

      // transient toast if available
      if (typeof window.showToast === 'function') {
        window.showToast('success', data.message || 'Item updated.');
      } else {
        console.log('Item updated:', data.message || '');
      }
    } catch (err) {
      console.error('Failed to apply live item update:', err);
    }
  }, { passive: true });
});
</script>

{{-- in your items index view, near other includes --}}
@include('admin.items._manual_modal')

@vite(['resources/js/item-manual-entry.js','resources/js/item-categories.js','resources/js/item-category-modal.js'])



<script>
  window.__serverCategories = @json($categories ?? []);
  window.__serverCategoryPpeMap = @json($categoryPpeMap ?? []);
</script>

