
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
    <div class="py-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <?php if (isset($component)) { $__componentOriginala29c4b6de1220dbc50317dc759b47929 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29c4b6de1220dbc50317dc759b47929 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.title','data' => ['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'clipboard-document-check','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'clipboard-document-check','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']); ?> Walk-in Requests  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala29c4b6de1220dbc50317dc759b47929)): ?>
<?php $attributes = $__attributesOriginala29c4b6de1220dbc50317dc759b47929; ?>
<?php unset($__attributesOriginala29c4b6de1220dbc50317dc759b47929); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala29c4b6de1220dbc50317dc759b47929)): ?>
<?php $component = $__componentOriginala29c4b6de1220dbc50317dc759b47929; ?>
<?php unset($__componentOriginala29c4b6de1220dbc50317dc759b47929); ?>
<?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text" id="walkin-live-search" placeholder="Search Borrower/Office" class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <a href="<?php echo e(route('admin.walkin.create')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 transition">
                            <i class="fas fa-plus-circle"></i> Create Walk-in Request
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pb-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
                <div class="table-container-no-scroll">
                <table class="w-full text-sm text-center text-gray-600 gov-table">
                    <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                        <tr>
                            <th class="px-6 py-3 text-center">ID</th>
                            <th class="px-6 py-3 text-center">Borrower</th>
                            <th class="px-6 py-3 text-center">Borrow Date</th>
                            <th class="px-6 py-3 text-center">Return Date</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="walkinTableBody" class="text-center">
                        <?php if (isset($component)) { $__componentOriginal112d5e476c92fff12a90a4ecc845ba67 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal112d5e476c92fff12a90a4ecc845ba67 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-loading-state','data' => ['colspan' => '6','dataWalkinLoadingRow' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-loading-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '6','data-walkin-loading-row' => true]); ?>
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
            </div>
        </div>
    </div>

    <template id="walkin-empty-state-template">
        <?php if (isset($component)) { $__componentOriginal4ffe5ef1be9b37746eb81577fa92d603 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4ffe5ef1be9b37746eb81577fa92d603 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-empty-state','data' => ['colspan' => '6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '6']); ?>
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

    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'walkinDetailsModal','maxWidth' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'walkinDetailsModal','maxWidth' => 'lg']); ?>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fa-solid fa-id-card text-purple-600"></i>
                    <span>Walk-in Details</span>
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="$dispatch('close-modal', 'walkinDetailsModal')">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div id="walkin-modal-content" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-user text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrower</div>
                        <div class="text-gray-600" data-field="borrower_name">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-building text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Office/Agency</div>
                        <div class="text-gray-600" data-field="office_agency">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-phone text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Contact</div>
                        <div class="text-gray-600" data-field="contact_number">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-map-marker-alt text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Address</div>
                        <div class="text-gray-600" data-field="address">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-day text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrow Schedule</div>
                        <div class="text-gray-600" data-field="borrowed_schedule">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return Schedule</div>
                        <div class="text-gray-600" data-field="returned_schedule">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-scroll text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Purpose</div>
                        <div class="text-gray-600" data-field="purpose">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-box-open text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Items</div>
                        <ul class="list-disc list-inside text-gray-600" data-field="items">—</ul>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-2">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','iconName' => 'x-circle','class' => 'px-4 py-2 text-sm','@click' => '$dispatch(\'close-modal\', \'walkinDetailsModal\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','iconName' => 'x-circle','class' => 'px-4 py-2 text-sm','@click' => '$dispatch(\'close-modal\', \'walkinDetailsModal\')']); ?>Close <?php echo $__env->renderComponent(); ?>
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

    
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'walkinDeliverConfirmModal','maxWidth' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'walkinDeliverConfirmModal','maxWidth' => 'md']); ?>
        <div class="p-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-truck text-purple-600 text-2xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Deliver Walk-in Request</h3>
            </div>

            <div class="mt-4 space-y-3">
                <p class="text-sm text-gray-600">
                    Are you sure you want to mark this walk-in request as delivered?
                </p>
                
                <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-user text-gray-400 mt-0.5"></i>
                        <div>
                            <span class="text-gray-500">Borrower:</span>
                            <span id="confirmBorrowerName" class="font-medium text-gray-900 ml-1">—</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-building text-gray-400 mt-0.5"></i>
                        <div>
                            <span class="text-gray-500">Office:</span>
                            <span id="confirmOfficeAgency" class="font-medium text-gray-900 ml-1">—</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-box-open text-gray-400 mt-0.5"></i>
                        <div class="w-full">
                            <span class="text-gray-500">Items:</span>
                            <ul id="confirmItemsList" class="list-disc list-inside text-gray-700 mt-1 ml-1">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-5">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','class' => 'px-4 py-2 text-sm','@click' => '$dispatch(\'close-modal\', \'walkinDeliverConfirmModal\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','class' => 'px-4 py-2 text-sm','@click' => '$dispatch(\'close-modal\', \'walkinDeliverConfirmModal\')']); ?>
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
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'walkinDeliverConfirmBtn','variant' => 'primary','class' => 'px-4 py-2 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'walkinDeliverConfirmBtn','variant' => 'primary','class' => 'px-4 py-2 text-sm']); ?>
                    <i class="fas fa-check-circle mr-1"></i> Confirm Delivery
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

    <script>
        window.WALKIN_LIST_ROUTE = "<?php echo e(route('admin.walkin.list')); ?>";
        window.WALKIN_PRINT_ROUTE_TEMPLATE = "<?php echo e(route('admin.walkin.print', ['walkInRequest' => '__ID__'])); ?>";
        window.WALKIN_DELIVER_ROUTE_TEMPLATE = "<?php echo e(route('admin.walkin.deliver', ['id' => '__ID__'])); ?>";
        window.CSRF_TOKEN = "<?php echo e(csrf_token()); ?>";
    </script>

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
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/walk-in/index.blade.php ENDPATH**/ ?>