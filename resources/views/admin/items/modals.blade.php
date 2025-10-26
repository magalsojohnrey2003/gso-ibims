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
      <template data-category-row-template>
        <tr data-category-row>
          <td class="px-3 py-2" data-category-name></td>
          <td class="px-3 py-2 text-right">
            <button type="button" class="text-blue-600 mr-2" data-view-cat>View</button>
            <button type="button" class="text-red-600" data-delete-cat>Delete</button>
          </td>
        </tr>
      </template>
      <template data-category-empty-template>
        <tr data-empty-state>
          <td colspan="2" class="px-3 py-2 text-gray-500">No categories</td>
        </tr>
      </template>
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
      <template data-office-row-template>
        <tr data-office-row>
          <td class="px-3 py-2" data-office-code></td>
          <td class="px-3 py-2" data-office-name></td>
          <td class="px-3 py-2 text-right">
            <button type="button" class="text-blue-600 mr-2" data-view-office>View</button>
            <button type="button" class="text-red-600" data-delete-office>Delete</button>
          </td>
        </tr>
      </template>
      <template data-office-empty-template>
        <tr data-empty-state>
          <td colspan="3" class="px-3 py-2 text-gray-500">No offices</td>
        </tr>
      </template>
    </div>

    <div class="mt-4 text-right">
      <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'manage-office')">Close</x-button>
    </div>
  </div>
</x-modal>

<!-- resources/views/admin/items/modals.blade.php -->
 
