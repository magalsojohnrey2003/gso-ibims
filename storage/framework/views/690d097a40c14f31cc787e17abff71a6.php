<!-- resources/views/admin/items/modals/office.blade.php -->
<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'manage-office','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'manage-office','maxWidth' => '2xl']); ?>
  <div class="w-full bg-white dark:bg-gray-900 shadow-lg overflow-hidden flex flex-col max-h-[85vh]">
    <div class="bg-yellow-600 text-white px-6 py-5 sticky top-0 z-20 relative">
      <button 
        type="button"
        x-on:click="$dispatch('close-modal', 'manage-office')"
        class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors p-2 hover:bg-white/10 rounded-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <h3 class="text-2xl font-bold flex items-center">
        <i class="fas fa-building mr-2"></i>
        MANAGE OFFICE CODES
      </h3>
      <p class="text-yellow-100 mt-2 text-sm leading-relaxed">Add an office code; after saving, office dropdowns will be populated.</p>
    </div>
    <div class="flex-1 overflow-y-auto relative p-6">

    <div id="manage-office-error" class="hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4"></div>

    <form id="office-manage-form" class="flex flex-col sm:flex-row gap-2 mb-4" onsubmit="return false;">
      <input id="new-office-code" type="text" placeholder="Office code (4 digits)" class="border rounded px-3 py-2 sm:w-1/3" inputmode="numeric" maxlength="4" pattern="\d{4}" title="Enter exactly 4 digits" />
      <input id="new-office-name" type="text" placeholder="Display name (optional)" class="border rounded px-3 py-2 w-1/3" />
      <button id="office-add-btn" type="button" class="px-4 py-2 bg-yellow-600 text-white rounded sm:w-auto">Save</button>
    </form>

    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm table-wrapper">
      <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700 gov-table">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wide">
          <tr>
            <th class="px-3 py-2">Code</th>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="office-list-body" class="divide-y divide-gray-100 bg-white">
          <?php if (isset($component)) { $__componentOriginal112d5e476c92fff12a90a4ecc845ba67 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal112d5e476c92fff12a90a4ecc845ba67 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-loading-state','data' => ['colspan' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-loading-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal112d5e476c92fff12a90a4ecc845ba67)): ?>
<?php $attributes = $__attributesOriginal112d5e476c92fff12a90a4ecc845ba67; ?>
<?php unset($__attributesOriginal112d5e476c92fff12a90a4ecc845ba67); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal112d5e476c92fff12a90a4ecc845ba67)): ?>
<?php $component = $__componentOriginal112d5e476c92fff12a90a4ecc845ba67; ?>
<?php unset($__componentOriginal112d5e476c92fff12a90a4ecc845ba67); ?>
<?php endif; ?>
        </tbody>
        </table>
      </div>
      <template data-office-row-template>
        <tr data-office-row>
          <td class="px-3 py-2" data-office-code></td>
          <td class="px-3 py-2" data-office-name></td>
          <td class="px-3 py-2 text-center">
            <button
              type="button"
              class="inline-flex items-center justify-center w-8 h-8 text-white bg-red-600 hover:bg-red-700 rounded transition-colors"
              data-delete-office
              title="Delete"
            >
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      </template>
      <template data-office-empty-template>
        <?php if (isset($component)) { $__componentOriginal4ffe5ef1be9b37746eb81577fa92d603 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4ffe5ef1be9b37746eb81577fa92d603 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-empty-state','data' => ['colspan' => '3','dataEmptyState' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '3','data-empty-state' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4ffe5ef1be9b37746eb81577fa92d603)): ?>
<?php $attributes = $__attributesOriginal4ffe5ef1be9b37746eb81577fa92d603; ?>
<?php unset($__attributesOriginal4ffe5ef1be9b37746eb81577fa92d603); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4ffe5ef1be9b37746eb81577fa92d603)): ?>
<?php $component = $__componentOriginal4ffe5ef1be9b37746eb81577fa92d603; ?>
<?php unset($__componentOriginal4ffe5ef1be9b37746eb81577fa92d603); ?>
<?php endif; ?>
      </template>
    </div>
    </div>
  </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/items/modals/office.blade.php ENDPATH**/ ?>