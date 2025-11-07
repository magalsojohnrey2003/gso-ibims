<!-- resources/views/admin/items/modals/category.blade.php -->
<x-modal name="manage-category" maxWidth="2xl">
  <div class="p-6">
    <h3 class="text-xl font-bold">Manage Categories</h3>
    <p class="text-sm text-gray-600 mb-4">Add a category; after saving, categories will appear in dropdowns.</p>

    <div id="manage-category-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="category-manage-form" class="flex flex-col sm:flex-row gap-2 mb-4" onsubmit="return false;">
      <input id="new-category-name" type="text" placeholder="Category name" class="border rounded px-3 py-2 sm:w-1/2" />
      <input id="new-category-code" type="text" placeholder="Category code (4 digits)" class="border rounded px-3 py-2 sm:w-1/3" inputmode="numeric" maxlength="4" pattern="\d{4}" title="Enter exactly 4 digits" />
      <button id="category-add-btn" type="button" class="px-4 py-2 bg-green-600 text-white rounded sm:w-auto">Save</button>
    </form>

    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm table-wrapper">
      <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700 gov-table">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
          <tr>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Code</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="category-list-body" class="divide-y divide-gray-100 bg-white">
          <!-- Filled by JS -->
        </tbody>
      </table>
      <template data-category-row-template>
        <tr data-category-row>
          <td class="px-3 py-2" data-category-name></td>
          <td class="px-3 py-2" data-category-code></td>
          <td class="px-3 py-2 text-right">
            <button type="button" class="text-blue-600 mr-2" data-view-cat>View</button>
            <button type="button" class="text-red-600" data-delete-cat>Delete</button>
          </td>
        </tr>
      </template>
      <template data-category-empty-template>
        <tr data-empty-state>
          <td colspan="3" class="px-3 py-2 text-gray-500">No categories</td>
        </tr>
      </template>
    </div>

    <div class="mt-4 text-right">
      <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'manage-category')">Close</x-button>
    </div>
  </div>
</x-modal>

