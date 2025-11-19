<!-- resources/views/admin/items/edit.blade.php -->
<?php
    $categoryMap = collect($categories ?? [])->filter()->keyBy('id')->map(fn ($c) => $c['name'])->toArray();

    $displayCategoryName = $item->category;
    if (is_numeric($item->category) && isset($categoryMap[(int) $item->category])) {
        $displayCategoryName = $categoryMap[(int) $item->category];
    } else {
        $displayCategoryName = $displayCategoryName ?? '';
    }

    $normalizedCategoryCode = array_change_key_case($categoryCodeMap ?? [], CASE_LOWER);
    $primaryInstance = $item->instances->first();

    $categoryCodeForCategory = '';
    if (is_numeric($item->category)) {
        $padded = str_pad((int) $item->category, 4, '0', STR_PAD_LEFT);
        $categoryCodeForCategory = substr(preg_replace('/\D/', '', strtoupper($padded)), 0, 4);
    } else {
        $raw = $normalizedCategoryCode[strtolower((string) $item->category)] ?? ($primaryInstance->category_code ?? '');
        $categoryCodeForCategory = $raw ? substr(preg_replace('/\D/', '', strtoupper($raw)), 0, 4) : '';
    }

    $existingPath = $item->photo ?? '';
    $hasCustomPhoto = filled($existingPath);
    $currentPhotoUrl = $item->photo_url;
    $defaultPhotoUrl = asset('images/item.png');

    $hasModelNo = $item->instances->contains(fn ($inst) => filled($inst->model_no ?? null));
    $hasSerialNo = $item->instances->contains(fn ($inst) => filled($inst->serial_no ?? null));
?>

<form
    id="edit-item-form-<?php echo e($item->id); ?>"
    method="POST"
    action="<?php echo e(route('items.update', $item->id)); ?>"
    enctype="multipart/form-data"
    class="space-y-6"
    data-property-form
    data-edit-item-form
    data-accordion-group="edit-item-<?php echo e($item->id); ?>"
    data-modal-name="edit-item-<?php echo e($item->id); ?>"
    data-photo-url="<?php echo e($currentPhotoUrl); ?>"
    data-photo-default="<?php echo e($defaultPhotoUrl); ?>"
    data-photo-custom="<?php echo e($hasCustomPhoto ? 'true' : 'false'); ?>">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div data-edit-feedback class="hidden rounded-md border border-transparent bg-green-50 px-4 py-3 text-sm text-green-700"></div>
    <div data-edit-error class="hidden rounded-md border border-transparent bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <input type="hidden" name="item_instance_id" value="<?php echo e($primaryInstance->id ?? ''); ?>">

    <!-- Step 1: Item Information -->
    <div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-accordion-target="edit-item-<?php echo e($item->id); ?>-info"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">1</div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Item Information</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
            </div>
        </button>

        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4" id="edit-item-<?php echo e($item->id); ?>-info" data-accordion-panel>
            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'name-'.e($item->id).'','value' => 'Item Name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'name-'.e($item->id).'','value' => 'Item Name']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'name-'.e($item->id).'','name' => 'name','type' => 'text','class' => 'mt-1 block w-full','value' => old('name', $item->name),'required' => true,'dataEditField' => 'name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'name-'.e($item->id).'','name' => 'name','type' => 'text','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name', $item->name)),'required' => true,'data-edit-field' => 'name']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('name'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('name')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
            </div>

            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'quantity-'.e($item->id).'','value' => 'Quantity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'quantity-'.e($item->id).'','value' => 'Quantity']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'quantity-'.e($item->id).'','name' => 'quantity_display','type' => 'number','class' => 'mt-1 block w-full','value' => old('quantity_display', $item->instances->count()),'min' => ''.e($item->instances->count()).'','dataQuantityInput' => true,'dataInitialQuantity' => ''.e($item->instances->count()).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'quantity-'.e($item->id).'','name' => 'quantity_display','type' => 'number','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('quantity_display', $item->instances->count())),'min' => ''.e($item->instances->count()).'','data-quantity-input' => true,'data-initial-quantity' => ''.e($item->instances->count()).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                <p class="mt-1 text-xs text-gray-500">You can increase quantity to add new property number rows. Decreasing is not allowed.</p>
            </div>

            <div>
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'description-'.e($item->id).'','value' => 'Description']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'description-'.e($item->id).'','value' => 'Description']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                <textarea
                    id="description-<?php echo e($item->id); ?>"
                    name="description"
                    rows="3"
                    class="block w-full px-3 py-2 text-sm border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 mt-1"
                    data-edit-field="description"><?php echo e(old('description', $primaryInstance?->notes)); ?></textarea>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('description'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('description')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Photo Upload</label>
                <div class="mb-3 flex items-center gap-4">
                    <img
                        id="edit-item-photo-preview-<?php echo e($item->id); ?>"
                        src="<?php echo e($currentPhotoUrl); ?>"
                        alt="Current photo for <?php echo e($item->name); ?>"
                        class="h-24 w-24 rounded-lg border border-gray-200 object-cover shadow-sm"
                        data-edit-photo-preview
                        data-default-photo="<?php echo e($defaultPhotoUrl); ?>">
                    <p class="text-xs text-gray-500 leading-4">
                        This thumbnail is used in the Items table and overview cards.
                    </p>
                </div>
                <input
                    id="photo-<?php echo e($item->id); ?>"
                    name="photo"
                    type="file"
                    accept="image/*"
                    data-filepond="true"
                    data-preview-height="120"
                    data-thumb-width="160" />
                <input type="hidden" name="existing_photo" value="<?php echo e($existingPath); ?>">
            </div>

        </div>
    </div>

    <input type="hidden" name="category" value="<?php echo e(old('category', $item->category)); ?>" data-edit-field="category">
    <input type="hidden" name="category_code" value="<?php echo e(old('category_code', $categoryCodeForCategory)); ?>" data-edit-field="category-code">

    <!-- Step 2: Existing Property Numbers -->
    <div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-accordion-target="edit-item-<?php echo e($item->id); ?>-instances"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">2</div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Existing Property Numbers</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
            </div>
        </button>

        <div id="edit-item-<?php echo e($item->id); ?>-instances" class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4" data-accordion-panel>
            <p class="text-sm text-gray-600">
                Fill out Year, Category Code, GLA, Serial, and Office for every row. Inputs with issues turn light red until corrected.
            </p>

            <div id="edit_instances_container" class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white dark:bg-gray-800" aria-live="polite" data-edit-instances-container>
                <?php $__empty_1 = true; $__currentLoopData = $item->instances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inst): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center gap-2 edit-instance-row bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-3 py-3" data-instance-id="<?php echo e($inst->id); ?>">
                        <div class="flex-none w-8 text-center">
                            <div class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-200 font-medium text-sm"><?php echo e($loop->iteration); ?></div>
                        </div>

                        <div class="flex items-center gap-2 flex-1">
                            <input
                                type="text"
                                class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-300 instance-part-year"
                                value="<?php echo e($inst->year_procured ?? ''); ?>"
                                placeholder="Year"
                                inputmode="numeric"
                                maxlength="4"
                                readonly>
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <input
                                type="text"
                                class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-300 instance-part-category"
                                value="<?php echo e($inst->category_code ?? $inst->category_id ?? ''); ?>"
                                placeholder="Category"
                                inputmode="numeric"
                                maxlength="4"
                                readonly>
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <input
                                type="text"
                                class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-300 instance-part-gla"
                                value="<?php echo e($inst->gla ?? ''); ?>"
                                placeholder="GLA"
                                inputmode="numeric"
                                maxlength="4"
                                readonly>
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <input
                                type="text"
                                class="w-20 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-serial"
                                value="<?php echo e($inst->serial ?? ''); ?>"
                                placeholder="Serial"
                                maxlength="5">
                            <span class="text-gray-500 dark:text-gray-400 select-none">-</span>

                            <select
                                class="w-24 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-office"
                                data-office-select
                                data-sync-office>
                                <option value="">Office</option>
                                <option value="<?php echo e($inst->office_code ?? ''); ?>" selected><?php echo e($inst->office_code ?? ''); ?></option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="instance-remove-btn flex-none inline-flex items-center justify-center text-red-600 hover:text-red-700 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            aria-label="Remove instance">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400">No property numbers found for this item.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Step 3: Serial and Model No. -->
    <div class="bg-gray-50 dark:bg-gray-800 shadow-md hover:shadow-lg transition rounded-lg mt-6" data-accordion-item data-edit-serial-section>
        <button
            type="button"
            class="w-full text-left focus:outline-none"
            data-accordion-trigger
            data-serial-model-trigger
            data-accordion-target="edit-item-<?php echo e($item->id); ?>-serial-model"
            aria-expanded="false">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">3</div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Serial and Model No.</h4>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" data-accordion-caret xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
            </div>
        </button>

        <div id="edit-item-<?php echo e($item->id); ?>-serial-model" class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-4" data-accordion-panel data-edit-serial-panel>
            <p class="text-sm text-gray-600" data-edit-serial-message>
                Provide serial and model numbers per property number once property number rows are complete.
            </p>

            <!-- Generate Model No. (for all rows) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Generate Model No. (for all rows)</label>
                    <input
                        type="text"
                        maxlength="100"
                        class="w-full px-3 py-2 border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200"
                        placeholder="Type a model number to apply to all rows"
                        data-model-generator>
                </div>
                <div class="text-xs text-gray-500 md:text-right">Applies to every Model No. field below.</div>
            </div>

            <div class="w-full space-y-3 max-h-72 overflow-auto p-3 border rounded-lg bg-white dark:bg-gray-800" data-edit-serial-container aria-live="polite">
                <?php $__empty_1 = true; $__currentLoopData = $item->instances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inst): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-indigo-50 dark:bg-indigo-900/30 space-y-3 edit-serial-row" data-instance-id="<?php echo e($inst->id); ?>">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300"><?php echo e($inst->property_number ?? 'N/A'); ?></div>
                        <div class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Serial No.</label>
                                <input
                                    type="text"
                                    maxlength="100"
                                    class="w-full px-3 py-2 border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 instance-part-serial-no"
                                    value="<?php echo e($inst->serial_no ?? ''); ?>"
                                    data-serial-model-input="serial_no"
                                    data-instance-id="<?php echo e($inst->id); ?>"
                                    <?php if (! ($hasSerialNo)): ?> disabled <?php endif; ?>>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Model No.</label>
                                <input
                                    type="text"
                                    maxlength="100"
                                    class="w-full px-3 py-2 border-2 border-gray-400 dark:border-gray-500 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-purple-600 dark:focus:border-purple-400 transition-all duration-200 instance-part-model-no"
                                    value="<?php echo e($inst->model_no ?? ''); ?>"
                                    data-serial-model-input="model_no"
                                    data-instance-id="<?php echo e($inst->id); ?>"
                                    <?php if (! ($hasModelNo)): ?> disabled <?php endif; ?>>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No property numbers available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-4 pt-2 flex justify-end gap-3 sticky bottom-0 z-20 pb-2">
        <!-- Edit Button (shown in readonly state) -->
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'primary','iconName' => 'pencil','type' => 'button','dataEditModeBtn' => true,'xOn:click' => 'window.dispatchEvent(new CustomEvent(\'edit-item:enable-edit\', { detail: { itemId: \''.e($item->id).'\' } }))']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','iconName' => 'pencil','type' => 'button','data-edit-mode-btn' => true,'x-on:click' => 'window.dispatchEvent(new CustomEvent(\'edit-item:enable-edit\', { detail: { itemId: \''.e($item->id).'\' } }))']); ?>
            Edit
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>

        <!-- Cancel Button (hidden in readonly state) -->
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','iconName' => 'x-mark','type' => 'button','dataEditCancelBtn' => true,'class' => 'hidden','xOn:click' => 'window.dispatchEvent(new CustomEvent(\'edit-item:cancel-edit\', { detail: { itemId: \''.e($item->id).'\' } }))']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','iconName' => 'x-mark','type' => 'button','data-edit-cancel-btn' => true,'class' => 'hidden','x-on:click' => 'window.dispatchEvent(new CustomEvent(\'edit-item:cancel-edit\', { detail: { itemId: \''.e($item->id).'\' } }))']); ?>
            Cancel
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>

        <!-- Update Button (hidden in readonly state) -->
        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'primary','iconName' => 'arrow-path','type' => 'submit','dataEditSubmit' => true,'class' => 'hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','iconName' => 'arrow-path','type' => 'submit','data-edit-submit' => true,'class' => 'hidden']); ?>
            Update
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
    </div>
</form>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/items/edit.blade.php ENDPATH**/ ?>