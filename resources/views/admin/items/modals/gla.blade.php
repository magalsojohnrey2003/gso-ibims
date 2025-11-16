<!-- resources/views/admin/items/modals/gla.blade.php -->
<x-modal name="manage-gla" maxWidth="2xl">
  <div class="w-full bg-white dark:bg-gray-900 shadow-lg overflow-hidden flex flex-col max-h-[85vh]">
    <div class="bg-purple-600 text-white px-6 py-5 sticky top-0 z-20 relative">
      <h3 class="text-2xl font-bold flex items-center" id="gla-modal-title">
        <i class="fas fa-tags mr-2"></i>
        MANAGE GLA
      </h3>
      <p class="text-purple-100 mt-2 text-sm leading-relaxed">Add GLA sub-categories for this PPE category.</p>
    </div>
    <div class="flex-1 overflow-y-auto relative p-6">

    <div id="manage-gla-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="gla-manage-form" class="flex flex-col sm:flex-row gap-2 mb-4" onsubmit="return false;">
      <input type="hidden" id="gla-parent-id" value="" />
      <input id="new-gla-name" type="text" placeholder="GLA name" class="border rounded px-3 py-2 w-32" />
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
          <x-table-loading-state colspan="3" />
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
        <x-table-empty-state colspan="3" data-empty-state />
      </template>
    </div>

    <div class="mt-4 flex justify-start">
      <button 
        type="button"
        x-on:click="$dispatch('close-modal', 'manage-gla')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
      </button>
    </div>
    </div>
  </div>
</x-modal>
