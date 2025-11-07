<!-- resources/views/admin/items/modals/office.blade.php -->
<x-modal name="manage-office" maxWidth="2xl">
  <div class="p-6">
    <h3 class="text-xl font-bold">Manage Office Codes</h3>
    <p class="text-sm text-gray-600 mb-4">Add an office code; after saving, office dropdowns will be populated.</p>

    <div id="manage-office-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="office-manage-form" class="flex flex-col sm:flex-row gap-2 mb-4" onsubmit="return false;">
      <input id="new-office-code" type="text" placeholder="Office code (4 digits)" class="border rounded px-3 py-2 sm:w-1/3" inputmode="numeric" maxlength="4" pattern="\d{4}" title="Enter exactly 4 digits" />
      <input id="new-office-name" type="text" placeholder="Display name (optional)" class="border rounded px-3 py-2 w-1/3" />
      <button id="office-add-btn" type="button" class="px-4 py-2 bg-yellow-600 text-white rounded sm:w-auto">Save</button>
    </form>

    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm table-wrapper">
      <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700 gov-table">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
          <tr>
            <th class="px-3 py-2">Code</th>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="office-list-body" class="divide-y divide-gray-100 bg-white">
          <!-- Filled by JS -->
        </tbody>
      </table>
      <template data-office-row-template>
        <tr data-office-row>
          <td class="px-3 py-2" data-office-code></td>
          <td class="px-3 py-2" data-office-name></td>
          <td class="px-3 py-2 text-right">
            <div class="inline-flex items-center gap-2">
              <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-200" data-view-office>
                <i class="fas fa-eye"></i>
                <span>View</span>
              </button>
              <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200" data-delete-office>
                <i class="fas fa-trash"></i>
                <span>Delete</span>
              </button>
            </div>
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
