<!-- resources/views/admin/items/modals.blade.php -->
<!-- Manage Category Modal -->
<x-modal name="manage-category" maxWidth="2xl">
  <div class="p-6">
    <h3 class="text-xl font-bold">Manage Categories</h3>
    <p class="text-sm text-gray-600 mb-4">Add a category; after saving, categories will appear in dropdowns.</p>

    <!-- Error area for category/category-code validation guidance -->
    <div id="manage-category-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="category-manage-form" class="flex gap-2 mb-4" onsubmit="return false;">
      <input id="new-category-name" type="text" placeholder="Category name" class="border rounded px-3 py-2 w-1/2" />
      <button id="category-add-btn" type="button" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
    </form>

    <div>
      <table class="w-full text-sm text-left text-gray-700">
        <thead class="text-xs uppercase text-gray-500">
          <tr><th>Name</th><th class="text-right">Actions</th></tr>
        </thead>
        <tbody id="category-list-body">
          <!-- Filled by JS -->
        </tbody>
      </table>
    </div>

    <div class="mt-4 text-right">
      <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'manage-category')">Close</x-button>
    </div>
  </div>
</x-modal>

<!-- Manage Office Modal -->
<x-modal name="manage-office" maxWidth="2xl">
  <div class="p-6">
    <h3 class="text-xl font-bold">Manage Office Codes</h3>
    <p class="text-sm text-gray-600 mb-4">Add an office code; after saving, office dropdowns will be populated.</p>

    <form id="office-manage-form" class="flex gap-2 mb-4" onsubmit="return false;">
      <input id="new-office-code" type="text" placeholder="Office code (1-4 alphanumeric)" class="border rounded px-3 py-2 w-1/3" />
      <input id="new-office-name" type="text" placeholder="Display name (optional)" class="border rounded px-3 py-2 w-1/3" />
      <button id="office-add-btn" type="button" class="px-4 py-2 bg-yellow-600 text-white rounded">Save</button>
    </form>

    <div>
      <table class="w-full text-sm text-left text-gray-700">
        <thead class="text-xs uppercase text-gray-500">
          <tr><th>Code</th><th>Name</th><th class="text-right">Actions</th></tr>
        </thead>
        <tbody id="office-list-body">
          <!-- Filled by JS -->
        </tbody>
      </table>
    </div>

    <div class="mt-4 text-right">
      <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'manage-office')">Close</x-button>
    </div>
  </div>
</x-modal>

<!-- resources/views/admin/items/modals.blade.php -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const categoryListBody = document.getElementById('category-list-body');
  const officeListBody = document.getElementById('office-list-body');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function renderCategories() {
    const cats = Array.isArray(window.__serverCategories) ? window.__serverCategories : [];
    if (!categoryListBody) return;
    categoryListBody.innerHTML = cats.map((c) => {
      const name = (typeof c === 'object' && c !== null) ? (c.name ?? '') : String(c || '');
      const idAttr = (typeof c === 'object' && c !== null) ? (c.id ?? '') : '';
      return `<tr>
                <td class="px-3 py-2">${name}</td>
                <td class="px-3 py-2 text-right">
                  <button data-view-cat class="text-blue-600 mr-2" data-id="${idAttr}">View</button>
                  <button data-delete-cat class="text-red-600" data-name="${encodeURIComponent(name)}" data-id="${idAttr}">Delete</button>
                </td>
              </tr>`;
    }).join('') || `<tr><td colspan="2" class="px-3 py-2 text-gray-500">No categories</td></tr>`;
  }

  function renderOffices() {
    const ofs = Array.isArray(window.__serverOffices) ? window.__serverOffices : [];
    if (!officeListBody) return;
    officeListBody.innerHTML = ofs.map((o) => {
      const code = (typeof o === 'object' ? (o.code ?? '') : o);
      const name = (typeof o === 'object' ? (o.name ?? '') : o);
      return `<tr>
                <td class="px-3 py-2">${code}</td>
                <td class="px-3 py-2">${name}</td>
                <td class="px-3 py-2 text-right">
                  <button data-view-office class="text-blue-600 mr-2" data-code="${code}">View</button>
                  <button data-delete-office class="text-red-600" data-code="${code}">Delete</button>
                </td>
              </tr>`;
    }).join('') || `<tr><td colspan="3" class="px-3 py-2 text-gray-500">No offices</td></tr>`;
  }

  async function fetchCategoriesFromServer() {
    try {
      const res = await fetch('/admin/api/categories', { headers: { Accept: 'application/json' }});
      if (!res.ok) throw new Error('Failed to load categories');
      const json = await res.json();
      // Normalize to array of objects {id, name}
      window.__serverCategories = Array.isArray(json.data)
        ? json.data.map(c => (typeof c === 'object' && c !== null) ? { id: c.id ?? null, name: c.name ?? '' } : { id: null, name: String(c) })
        : [];
      renderCategories();
      window.dispatchEvent(new Event('server:categories:updated'));
    } catch (e) {
      console.warn('Categories load failed', e);
      renderCategories();
    }
  }

  async function fetchOfficesFromServer() {
    try {
      const res = await fetch('/admin/api/offices', { headers: { Accept: 'application/json' }});
      if (!res.ok) throw new Error('Failed to load offices');
      const json = await res.json();
      window.__serverOffices = Array.isArray(json.data) ? json.data : [];
      renderOffices();
      window.dispatchEvent(new Event('server:offices:updated'));
    } catch (e) {
      console.warn('Offices load failed', e);
      renderOffices();
    }
  }

  // initial render (prefer server copy if present)
  if (!Array.isArray(window.__serverCategories) || window.__serverCategories.length === 0) {
    fetchCategoriesFromServer();
  } else {
    // ensure normalized shape
    window.__serverCategories = window.__serverCategories.map(c => (typeof c === 'object' && c !== null) ? { id: c.id ?? null, name: c.name ?? String(c) } : { id: null, name: String(c) });
    renderCategories();
  }

  if (!Array.isArray(window.__serverOffices) || window.__serverOffices.length === 0) {
    fetchOfficesFromServer();
  } else {
    renderOffices();
  }

  // Add category (AJAX)
  document.getElementById('category-add-btn')?.addEventListener('click', async () => {
    const input = document.getElementById('new-category-name');
    if (!input) return;
    const name = input.value.trim();
    if (!name) { alert('Enter category name'); return; }

    try {
      const res = await fetch('/admin/api/categories', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json'
        },
        body: JSON.stringify({ name })
      });
      if (!res.ok) {
        const err = await res.json().catch(()=>null);
        throw new Error(err?.message || 'Failed to save category');
      }
      input.value = '';
      // After successful save prefer fetching from server to get canonical id
      await fetchCategoriesFromServer();
      alert('Category saved');
    } catch (err) {
      alert(err.message || 'Failed to save category');
    }
  });

  // Add office (AJAX)
  document.getElementById('office-add-btn')?.addEventListener('click', async () => {
    const codeEl = document.getElementById('new-office-code');
    const nameEl = document.getElementById('new-office-name');
    if (!codeEl) return;
    const code = codeEl.value.trim();
    if (!/^[A-Za-z0-9]{1,4}$/.test(code)) { alert('Office code should be 1-4 alphanumeric'); return; }
    const display = nameEl?.value?.trim() || '';
    try {
      const res = await fetch('/admin/api/offices', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json'
        },
        body: JSON.stringify({ code, name: display })
      });
      if (!res.ok) {
        const err = await res.json().catch(()=>null);
        throw new Error(err?.message || 'Failed to save office');
      }
      codeEl.value = '';
      if (nameEl) nameEl.value = '';
      await fetchOfficesFromServer();
      alert('Office saved');
    } catch (err) {
      alert(err.message || 'Failed to save office');
    }
  });

  // Delete categories/offices (delegated)
  categoryListBody?.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('[data-delete-cat]');
    if (!btn) return;
    const name = btn.getAttribute('data-name');
    if (!confirm(`Delete category "${name}"?`)) return;
    try {
      const res = await fetch(`/admin/api/categories/${encodeURIComponent(name)}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
      });
      if (!res.ok) {
        const err = await res.json().catch(()=>null);
        throw new Error(err?.message || 'Failed to delete');
      }
      await fetchCategoriesFromServer();
    } catch (err) {
      alert(err.message || 'Failed to delete category');
    }
  });

  officeListBody?.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('[data-delete-office]');
    if (!btn) return;
    const code = btn.getAttribute('data-code');
    if (!confirm(`Delete office "${code}"?`)) return;
    try {
      const res = await fetch(`/admin/api/offices/${encodeURIComponent(code)}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
      });
      if (!res.ok) {
        const err = await res.json().catch(()=>null);
        throw new Error(err?.message || 'Failed to delete');
      }
      await fetchOfficesFromServer();
    } catch (err) {
      alert(err.message || 'Failed to delete office');
    }
  });

  // View buttons (simple alerts as before)
  categoryListBody?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-view-cat]');
    if (!btn) return;
    const row = btn.closest('tr');
    const name = row?.querySelector('td')?.textContent || '';
    alert('View items in category: ' + name + '\n(Implement server-side view to display associated items.)');
  });

  officeListBody?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-view-office]');
    if (!btn) return;
    const code = btn.getAttribute('data-code');
    alert('View items for office: ' + code + '\n(Implement server-side view to display associated items.)');
  });

});
</script>