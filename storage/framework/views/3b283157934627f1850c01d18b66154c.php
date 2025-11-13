<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="py-10 px-6 max-w-2xl mx-auto">
        <div class="bg-white shadow-lg rounded-2xl p-8 text-center space-y-6">
            <div class="space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full <?php echo e($updated ? 'bg-teal-100 text-teal-600' : 'bg-gray-100 text-gray-500'); ?>">
                    <i class="fas <?php echo e($updated ? 'fa-qrcode' : 'fa-info-circle'); ?> text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">
                    QR Verification <?php echo e($updated ? 'Complete' : 'Already Verified'); ?>

                </h1>
                <p class="text-gray-600">
                    <?php echo e($message); ?>

                </p>
            </div>

            <div class="space-y-2 text-sm text-gray-600">
                <?php
                    $statusValue = $borrowRequest->status === 'qr_verified'
                        ? 'approved'
                        : ($borrowRequest->status ?? 'pending');
                    $statusLabel = ucwords(str_replace('_', ' ', $statusValue));
                    $badgeType = match ($statusValue) {
                        'approved' => 'accepted',
                        'validated' => 'info',
                        'pending' => 'pending',
                        'returned' => 'success',
                        'rejected' => 'rejected',
                        default => 'info',
                    };
                ?>
                <div>
                    <span class="font-semibold text-gray-800">Request ID:</span>
                    <span class="ml-1 text-gray-700">#<?php echo e($borrowRequest->id); ?></span>
                </div>
                <div class="inline-flex items-center gap-2">
                    <span class="font-semibold text-gray-800">Current Status:</span>
                    <?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($badgeType),'text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusLabel)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
                </div>
            </div>

            <?php if($downloadUrl): ?>
                <div class="pt-2">
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['as' => 'a','href' => ''.e($downloadUrl).'','target' => '_blank','rel' => 'noopener','variant' => 'primary','iconName' => 'arrow-down-tray','class' => 'px-4 py-2 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['as' => 'a','href' => ''.e($downloadUrl).'','target' => '_blank','rel' => 'noopener','variant' => 'primary','iconName' => 'arrow-down-tray','class' => 'px-4 py-2 text-sm']); ?>
                        Download Saved Form
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
            <?php endif; ?>

            <div class="pt-4 flex justify-center">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['as' => 'a','href' => ''.e(route('borrow.requests')).'','variant' => 'secondary','iconName' => 'arrow-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['as' => 'a','href' => ''.e(route('borrow.requests')).'','variant' => 'secondary','iconName' => 'arrow-left']); ?>
                    Back to Borrow Requests
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
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/borrow-requests/scan-result.blade.php ENDPATH**/ ?>