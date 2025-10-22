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

<script>
    window.__serverCategories = @json(collect($categories)->map(function($c){
        return ['id' => $c['id'] ?? null, 'name' => $c['name'] ?? ''];
    })->values());
    window.__serverOffices = @json($offices ?? []);
    window.__servercategoryCodeMap = @json($categoryCodeMap ?? []);
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

<script>
document.addEventListener('DOMContentLoaded', () => {
  // listen for item edit success to update row inline
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

<!-- Category/Office management and wiring script (keeps behavior client-only when backend endpoints are not available) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // This script expects window.__serverCategories (array) and window.__serverOffices (array of objects or codes)
  function renderCategories() {
    const body = document.getElementById('category-list-body');
    if (!body) return;
    const cats = Array.isArray(window.__serverCategories) ? window.__serverCategories : [];
    body.innerHTML = cats.map((c, idx) => {
      const name = (typeof c === 'object' && c !== null) ? (c.name ?? '') : String(c || '');
      const idAttr = (typeof c === 'object' && c !== null) ? (c.id ?? '') : '';
      return `<tr data-category-row="${idx}">
                <td class="px-3 py-2">${name}</td>
                <td class="px-3 py-2 text-right">
                  <button data-view-cat class="text-blue-600 mr-2" data-id="${idAttr}">View</button>
                  <button data-delete-cat class="text-red-600" data-name="${encodeURIComponent(name)}" data-id="${idAttr}">Delete</button>
                </td>
              </tr>`;
    }).join('') || `<tr><td colspan="2" class="px-3 py-2 text-gray-500">No categories</td></tr>`;
  }

  function renderOffices() {
    const body = document.getElementById('office-list-body');
    if (!body) return;
    const ofs = Array.isArray(window.__serverOffices) ? window.__serverOffices : [];
    body.innerHTML = ofs.map((o, idx) => {
      const code = (typeof o === 'object' ? (o.code ?? '') : o);
      const name = (typeof o === 'object' ? (o.name ?? '') : '');
      return `<tr data-office-row="${idx}">
                <td class="px-3 py-2">${code}</td>
                <td class="px-3 py-2">${name}</td>
                <td class="px-3 py-2 text-right">
                  <button data-view-office class="text-blue-600 mr-2" data-code="${code}">View</button>
                  <button data-delete-office class="text-red-600" data-code="${code}">Delete</button>
                </td>
              </tr>`;
    }).join('') || `<tr><td colspan="3" class="px-3 py-2 text-gray-500">No offices</td></tr>`;
  }

  renderCategories();
  renderOffices();

  document.getElementById('category-add-btn')?.addEventListener('click', () => {
    const input = document.getElementById('new-category-name');
    if (!input) return;
    const name = input.value.trim();
    if (!name) { alert('Enter category name'); return; }
    window.__serverCategories = window.__serverCategories || [];
    // push as object shape to keep consistency with server shape
    if (!window.__serverCategories.some(c => ((typeof c === 'object' && c !== null) ? (c.name ?? '') : String(c || '')) === name)) {
      window.__serverCategories.push({ id: null, name });
    }
    input.value = '';
    renderCategories();
    window.dispatchEvent(new Event('server:categories:updated'));
  });

  document.getElementById('office-add-btn')?.addEventListener('click', () => {
    const codeEl = document.getElementById('new-office-code');
    const nameEl = document.getElementById('new-office-name');
    if (!codeEl) return;
    const code = codeEl.value.trim().toUpperCase();
    if (!/^[A-Za-z0-9]{1,4}$/.test(code)) { alert('Office code should be 1-4 alphanumeric'); return; }
    const display = nameEl?.value?.trim() || '';
    window.__serverOffices = window.__serverOffices || [];
    if (!window.__serverOffices.some(o => (o.code || o) === code)) {
      window.__serverOffices.push({ code, name: display });
    }
    codeEl.value = '';
    if (nameEl) nameEl.value = '';
    renderOffices();
    window.dispatchEvent(new Event('server:offices:updated'));
  });

  document.getElementById('category-list-body')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-delete-cat]');
    if (!btn) return;
    const encoded = btn.getAttribute('data-name');
    const name = decodeURIComponent(encoded || '');
    if (!confirm(`Delete category "${name}"? (Deletion disabled in UI if in use)`)) return;
    window.__serverCategories = (window.__serverCategories || []).filter(c => ((typeof c === 'object' && c !== null) ? (c.name ?? '') : String(c)) !== name);
    renderCategories();
    window.dispatchEvent(new Event('server:categories:updated'));
  });

  document.getElementById('office-list-body')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-delete-office]');
    if (!btn) return;
    const code = btn.getAttribute('data-code');
    if (!confirm(`Delete office "${code}"?`)) return;
    window.__serverOffices = window.__serverOffices || [];
    window.__serverOffices = window.__serverOffices.filter(o => ((o.code || o) !== code));
    renderOffices();
    window.dispatchEvent(new Event('server:offices:updated'));
  });

  document.getElementById('category-list-body')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-view-cat]');
    if (!btn) return;
    const row = btn.closest('tr');
    const name = row?.querySelector('td')?.textContent || '';
    alert('View items in category: ' + name + '\n(Implement server-side view to display associated items.)');
  });

  document.getElementById('office-list-body')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-view-office]');
    if (!btn) return;
    const code = btn.getAttribute('data-code');
    alert('View items for office: ' + code + '\n(Implement server-side view to display associated items.)');
  });

  // When server globals update, re-render selects in forms (listeners in JS modules will pick up this event)
  window.addEventListener('server:categories:updated', () => window.dispatchEvent(new Event('open-modal')));
  window.addEventListener('server:offices:updated', () => window.dispatchEvent(new Event('open-modal')));
});
</script>

@vite(['resources/js/app.js'])