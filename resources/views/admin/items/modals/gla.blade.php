<!-- resources/views/admin/items/modals/gla.blade.php -->
<x-modal name="manage-gla" maxWidth="2xl">
  <div class="p-6">
    <h3 class="text-xl font-bold" id="gla-modal-title">Manage GLA</h3>
    <p class="text-sm text-gray-600 mb-4">Add GLA sub-categories for this PPE category.</p>

    <div id="manage-gla-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="gla-manage-form" class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 mb-4" onsubmit="return false;">
      <input type="hidden" id="gla-parent-id" value="" />
      <input id="new-gla-name" type="text" placeholder="GLA name" class="border rounded px-3 py-2 w-full" />
      <input id="new-gla-code" type="text" placeholder="GLA code (1-4 digits)" class="border rounded px-3 py-2 w-32" inputmode="numeric" maxlength="4" pattern="\d{1,4}" title="Enter 1-4 digits" />
      <button id="gla-add-btn" type="button" class="px-4 py-2 bg-green-600 text-white rounded whitespace-nowrap">Save</button>
    </form>

    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm table-wrapper">
      <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700 gov-table">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
          <tr>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Code</th>
            <th class="px-3 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="gla-list-body" class="divide-y divide-gray-100 bg-white">
          <!-- Filled by JS -->
        </tbody>
        </table>
      </div>
      <template data-gla-row-template>
        <tr data-gla-row>
          <td class="px-3 py-2" data-gla-name></td>
          <td class="px-3 py-2" data-gla-code></td>
          <td class="px-3 py-2 text-center">
            <button type="button" class="inline-flex items-center justify-center w-8 h-8 text-white bg-red-600 hover:bg-red-700 rounded transition-colors" data-delete-gla title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      </template>
      <template data-gla-empty-template>
        <tr data-empty-state>
          <td colspan="3" class="px-3 py-2 text-gray-500">No GLA sub-categories yet</td>
        </tr>
      </template>
    </div>

    <div class="mt-4 text-right">
      <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'manage-gla')">Close</x-button>
    </div>
  </div>
</x-modal>
