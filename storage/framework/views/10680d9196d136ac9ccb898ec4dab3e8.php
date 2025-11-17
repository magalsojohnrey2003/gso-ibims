
<tr data-user-id="<?php echo e($user->id); ?>">
    <td class="px-6 py-4 whitespace-nowrap max-w-xs truncate" title="<?php echo e($user->full_name ?? ($user->first_name . ' ' . $user->last_name)); ?>"><?php echo e($user->full_name ?? ($user->first_name . ' ' . $user->last_name)); ?></td>
    <td class="px-6 py-4 whitespace-nowrap"><?php echo e($user->email); ?></td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="inline-flex items-center justify-center text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-700">
            <?php echo e($user->creation_source ?? 'Borrower-Registered'); ?>

        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap"><?php echo e($user->created_at->format('Y-m-d')); ?></td>
    <td class="px-6 py-4 whitespace-nowrap">
        <?php if($user->last_login_at): ?>
            <span class="inline-flex items-center gap-1.5 text-sm" data-last-active="<?php echo e($user->last_login_at->timestamp); ?>" title="Last login: <?php echo e($user->last_login_at->format('M d, Y h:i A')); ?>">
                <i class="fas fa-circle text-xs <?php echo e($user->last_login_at->isToday() ? 'text-green-500' : ($user->last_login_at->gt(now()->subDays(7)) ? 'text-yellow-500' : 'text-gray-400')); ?>"></i>
                <span class="last-active-text"><?php echo e($user->last_login_at->diffForHumans()); ?></span>
            </span>
        <?php else: ?>
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-400">
                <i class="fas fa-circle text-xs text-gray-300"></i>
                <span>Never logged in</span>
            </span>
        <?php endif; ?>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-center gap-3">
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'primary','size' => 'sm','class' => 'h-10 w-10 !px-0 !py-0 rounded-full shadow-lg [&>span:first-child]:mr-0 [&>span:last-child]:sr-only open-edit-modal','iconName' => 'pencil-square','dataEditUrl' => ''.e(route('admin.users.edit', $user)).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','size' => 'sm','class' => 'h-10 w-10 !px-0 !py-0 rounded-full shadow-lg [&>span:first-child]:mr-0 [&>span:last-child]:sr-only open-edit-modal','iconName' => 'pencil-square','data-edit-url' => ''.e(route('admin.users.edit', $user)).'']); ?>
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
            <form class="inline ajax-delete" action="<?php echo e(route('admin.users.destroy', $user)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'danger','size' => 'sm','class' => 'h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only','iconName' => 'trash','type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'danger','size' => 'sm','class' => 'h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only','iconName' => 'trash','type' => 'submit']); ?>
                    Delete
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
            </form>
        </div>
    </td>
</tr>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/users/_row.blade.php ENDPATH**/ ?>