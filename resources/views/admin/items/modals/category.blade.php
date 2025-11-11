<!-- resources/views/admin/items/modals/category.blade.php -->
<x-modal name="manage-category" maxWidth="2xl">
  <div class="w-full bg-white dark:bg-gray-900 shadow-lg overflow-hidden flex flex-col max-h-[85vh]">
    <div class="bg-green-600 text-white px-6 py-5 sticky top-0 z-20 relative">
      <button 
        type="button"
        x-on:click="$dispatch('close-modal', 'manage-category')"
        class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors p-2 hover:bg-white/10 rounded-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <h3 class="text-2xl font-bold flex items-center">
        <i class="fas fa-list-alt mr-2"></i>
        MANAGE CATEGORIES
      </h3>
      <p class="text-green-100 mt-2 text-sm leading-relaxed">Add a category; after saving, categories will appear in dropdowns.</p>
    </div>
    <div class="flex-1 overflow-y-auto relative p-6">

    <div id="manage-category-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="category-manage-form" class="flex flex-col sm:flex-row gap-2 mb-4" onsubmit="return false;">
      <input id="new-category-name" type="text" placeholder="Category name" class="border rounded px-3 py-2 sm:w-1/2" />
      <input id="new-category-code" type="text" placeholder="Category code (4 digits)" class="border rounded px-3 py-2 sm:w-1/3" inputmode="numeric" maxlength="4" pattern="\d{4}" title="Enter exactly 4 digits" />
      <button id="category-add-btn" type="button" class="px-4 py-2 bg-green-600 text-white rounded sm:w-auto">Save</button>
    </form>

    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm table-wrapper">
      <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700 gov-table">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
          <tr>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Code</th>
            <th class="px-3 py-2 text-center">Manage GLA</th>
            <th class="px-3 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="category-list-body" class="divide-y divide-gray-100 bg-white">
          <!-- Filled by JS -->
        </tbody>
        </table>
      </div>
      <template data-category-row-template>
        <tr data-category-row>
          <td class="px-3 py-2" data-category-name></td>
          <td class="px-3 py-2" data-category-code></td>
          <td class="px-3 py-2 text-center">
            <button type="button" class="inline-flex items-center justify-center w-8 h-8 text-white bg-purple-600 hover:bg-purple-700 rounded transition-colors" data-manage-gla title="Manage GLA">
              <i class="fas fa-list"></i>
            </button>
          </td>
          <td class="px-3 py-2 text-center">
            <button type="button" class="inline-flex items-center justify-center w-8 h-8 text-white bg-red-600 hover:bg-red-700 rounded transition-colors" data-delete-cat title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      </template>
      <template data-category-empty-template>
        <tr data-empty-state>
          <td colspan="3" class="px-3 py-2 text-gray-500">No categories</td>
        </tr>
      </template>
    </div>
    </div>
  </div>
</x-modal>

