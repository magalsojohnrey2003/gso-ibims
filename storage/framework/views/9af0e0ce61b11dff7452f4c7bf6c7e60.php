<?php
    $primaryInstance = $item->instances->first();
    $photoUrl = $item->photo_url;
    $hasUploadedPhoto = filled($item->photo ?? $primaryInstance?->photo);

    $description = $primaryInstance?->notes;
    $categoryLabel = $displayCategory ?? ($item->category ?? '');
    $updatedAt = optional($item->updated_at)->format('M d, Y g:i A');
    $createdAt = optional($item->created_at)->format('M d, Y g:i A');
    $acquisitionDateDisplay = optional($item->acquisition_date)->format('M d, Y');
    $acquisitionCostDisplay = $item->acquisition_cost !== null
        ? '₱' . number_format($item->acquisition_cost, 0)
        : null;
?>

<div class="space-y-8">
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h4 class="text-lg font-semibold text-gray-900">Item Information</h4>
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">
                <i class="fas fa-hashtag"></i>
                ID: <?php echo e($item->id); ?>

            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr,280px]">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Item Name</span>
                    <p class="text-base font-semibold text-gray-900"><?php echo e($item->name); ?></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Category</span>
                    <p class="text-base font-semibold text-gray-900"><?php echo e($categoryLabel ?: '—'); ?></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Total Quantity</span>
                    <p class="text-base font-semibold text-gray-900"><?php echo e($item->total_qty); ?></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Available</span>
                    <p class="text-base font-semibold <?php echo e($item->available_qty > 0 ? 'text-green-600' : 'text-red-600'); ?>">
                        <?php echo e($item->available_qty); ?>

                    </p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Acquisition Date</span>
                    <p class="text-base font-medium text-gray-900"><?php echo e($acquisitionDateDisplay ?: '—'); ?></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Acquisition Cost</span>
                    <p class="text-base font-medium text-gray-900"><?php echo e($acquisitionCostDisplay ?: '—'); ?></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Created</span>
                    <p class="text-base font-medium text-gray-900"><?php echo e($createdAt ?: '—'); ?></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Last Updated</span>
                    <p class="text-base font-medium text-gray-900"><?php echo e($updatedAt ?: '—'); ?></p>
                </div>
            </div>

            <div class="flex justify-center items-center">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 w-full max-w-[240px]">
                <div class="w-full h-40 rounded-lg bg-white border border-gray-200 flex flex-col items-center justify-center overflow-hidden shadow">
                    <img
                        src="<?php echo e($photoUrl); ?>"
                        alt="<?php echo e($item->name); ?> photo"
                        class="w-full h-full object-cover">
                    <?php if (! ($hasUploadedPhoto)): ?>
                        <span class="text-xs text-gray-500 py-1 bg-white/80 w-full text-center">Default placeholder shown</span>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>

        <?php if($description): ?>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <span class="block text-xs uppercase tracking-wide text-gray-500 mb-2">Description / Notes</span>
                <p class="text-sm leading-relaxed text-gray-700 whitespace-pre-line"><?php echo e($description); ?></p>
            </div>
        <?php endif; ?>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="text-lg font-semibold text-gray-900">Property Numbers</h4>
            <span class="text-xs text-gray-500"><?php echo e($item->instances->count()); ?> item<?php echo e($item->instances->count() === 1 ? '' : 's'); ?></span>
        </div>

        <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
            <div class="table-container-no-scroll" style="max-height: 18rem;">
                <table class="w-full text-sm text-center text-gray-600 gov-table">
                    <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                        <tr>
                            <th class="px-6 py-3 whitespace-nowrap">Property Numbers</th>
                            <th class="px-6 py-3">Serial No.</th>
                            <th class="px-6 py-3">Model No.</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">History</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php $__empty_1 = true; $__currentLoopData = $item->instances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $instance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50" data-item-instance-id="<?php echo e($instance->id); ?>">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo e($instance->property_number); ?></td>
                                <td class="px-6 py-4">
                                    <?php $sn = $instance->serial_no ?? '—'; ?>
                                    <span class="inline-block max-w-[3rem] truncate align-middle cursor-help" title="<?php echo e($sn); ?>"><?php echo e($sn); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php $mn = $instance->model_no ?? '—'; ?>
                                    <span class="inline-block max-w-[3rem] truncate align-middle cursor-help" title="<?php echo e($mn); ?>"><?php echo e($mn); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                        $status = strtolower($instance->status ?? 'unknown');
                                        $statusClasses = [
                                            'available' => 'bg-green-100 text-green-700',
                                            'borrowed' => 'bg-indigo-100 text-indigo-700',
                                            'returned' => 'bg-emerald-100 text-emerald-700',
                                            'missing' => 'bg-red-100 text-red-700',
                                            'damaged' => 'bg-amber-100 text-amber-700',
                                            'minor_damage' => 'bg-yellow-100 text-yellow-700',
                                            'pending' => 'bg-gray-200 text-gray-700',
                                        ];
                                        $statusIcons = [
                                            'available' => 'fa-check-circle',
                                            'borrowed' => 'fa-box',
                                            'returned' => 'fa-arrow-left',
                                            'missing' => 'fa-exclamation-triangle',
                                            'damaged' => 'fa-exclamation-triangle',
                                            'minor_damage' => 'fa-exclamation-circle',
                                            'pending' => 'fa-clock',
                                            'unknown' => 'fa-question-circle',
                                        ];
                                        $badgeClass = $statusClasses[$status] ?? 'bg-gray-200 text-gray-700';
                                        $badgeIcon = $statusIcons[$status] ?? 'fa-question-circle';
                                        $badgeBase = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold';
                                        $statusLabel = ucwords(str_replace('_', ' ', $status));
                                    ?>
                                    <span class="<?php echo e($badgeBase); ?> <?php echo e($badgeClass); ?>" data-instance-status data-badge-base="<?php echo e($badgeBase); ?>">
                                        <i class="fas <?php echo e($badgeIcon); ?> text-xs"></i>
                                        <span><?php echo e($statusLabel); ?></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','size' => 'sm','class' => 'btn-action btn-utility h-9 w-9 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only','iconName' => 'clock','xData' => true,'xOn:click.prevent' => 'window.dispatchEvent(new CustomEvent(\'item-history:open\', { detail: { instanceId: '.e($instance->id).', propertyNumber: \''.e($instance->property_number).'\' } }))']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','size' => 'sm','class' => 'btn-action btn-utility h-9 w-9 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only','iconName' => 'clock','x-data' => true,'x-on:click.prevent' => 'window.dispatchEvent(new CustomEvent(\'item-history:open\', { detail: { instanceId: '.e($instance->id).', propertyNumber: \''.e($instance->property_number).'\' } }))']); ?>
                                        History
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
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-500">
                                    No Property Numbers are currently linked to this item.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/admin/items/view.blade.php ENDPATH**/ ?>