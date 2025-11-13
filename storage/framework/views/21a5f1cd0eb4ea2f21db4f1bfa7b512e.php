<?php if (isset($component)) { $__componentOriginal69dc84650370d1d4dc1b42d016d7226b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b = $attributes; } ?>
<?php $component = App\View\Components\GuestLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('guest-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\GuestLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-50 to-indigo-100 px-4 py-12">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-8 <?php echo e($success ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-rose-600'); ?> text-white">
                    <div class="flex items-center justify-center mb-4">
                        <?php if($success): ?>
                            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-5xl text-green-500"></i>
                            </div>
                        <?php else: ?>
                            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center">
                                <i class="fas fa-times text-5xl text-red-500"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-2xl font-bold text-center">
                        <?php echo e($success ? 'Approval Successful!' : 'Approval Failed'); ?>

                    </h1>
                </div>

                <!-- Content -->
                <div class="px-6 py-8">
                    <div class="text-center mb-6">
                        <p class="text-gray-700 text-lg"><?php echo e($message); ?></p>
                    </div>

                    <!-- Request Details -->
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-600">Request ID:</span>
                            <span class="text-sm font-semibold text-gray-900">#<?php echo e($request->id); ?></span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-600">Borrower:</span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo e($request->borrower_name); ?></span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-medium text-gray-600">Status:</span>
                            <span class="text-sm">
                                <?php if($request->status === 'pending'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php elseif($request->status === 'approved'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                <?php elseif($request->status === 'delivered'): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <i class="fas fa-box-check"></i> Delivered
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if($request->office_agency): ?>
                            <div class="flex justify-between items-start">
                                <span class="text-sm font-medium text-gray-600">Office/Agency:</span>
                                <span class="text-sm text-gray-900"><?php echo e($request->office_agency); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 space-y-3">
                        <?php if($success): ?>
                            <a href="<?php echo e(route('admin.walkin.index')); ?>" 
                               class="block w-full text-center px-4 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-list mr-2"></i>
                                View All Walk-in Requests
                            </a>
                        <?php else: ?>
                            <button onclick="window.history.back()" 
                                    class="block w-full text-center px-4 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Go Back
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <p class="text-xs text-center text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo e(now()->format('F j, Y g:i A')); ?>

                    </p>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $attributes = $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $component = $__componentOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/walk-in/qr-result.blade.php ENDPATH**/ ?>