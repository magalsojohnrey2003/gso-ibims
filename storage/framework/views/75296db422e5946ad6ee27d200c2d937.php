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
    <?php $noMainScroll = false; ?>

    <div class="py-2">
        <div class="px-2">
            <div id="adminManpowerAlert" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <?php if (isset($component)) { $__componentOriginala29c4b6de1220dbc50317dc759b47929 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29c4b6de1220dbc50317dc759b47929 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.title','data' => ['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'users','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'users','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']); ?> Manpower Requests  <?php echo $__env->renderComponent(); ?>
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
                            <input type="text" id="admin-manpower-search" placeholder="Search name, role, purpose"
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <div class="flex-shrink-0 relative">
                            <select id="admin-manpower-status" class="border border-gray-300 rounded-lg pl-4 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400 appearance-none bg-white">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'openManageRoles','variant' => 'secondary','iconName' => 'cog','class' => 'px-4 py-2 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'openManageRoles','variant' => 'secondary','iconName' => 'cog','class' => 'px-4 py-2 text-sm']); ?>Manage Role <?php echo $__env->renderComponent(); ?>
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
        </div>
    </div>

    <div class="pb-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
                <div class="table-container-no-scroll">
                    <table class="w-full text-sm text-center text-gray-600 gov-table">
                        <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                            <tr>
                                <th class="px-6 py-3 text-center">User</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3 text-center">Qty (Requested)</th>
                                <th class="px-6 py-3 text-center">Schedule</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adminManpowerTableBody" class="text-center">
                            <?php if (isset($component)) { $__componentOriginal112d5e476c92fff12a90a4ecc845ba67 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal112d5e476c92fff12a90a4ecc845ba67 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-loading-state','data' => ['colspan' => '6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-loading-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '6']); ?>
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

    <template id="admin-manpower-empty-state-template">
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

    <template id="badge-status-pending"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'pending','text' => 'Pending']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?></template>
    <template id="badge-status-approved"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'accepted','text' => 'Approved']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?></template>
    <template id="badge-status-rejected"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'rejected','text' => 'Rejected']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?></template>

    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'adminManageRolesModal','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'adminManageRolesModal','maxWidth' => '2xl']); ?>
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Manage Roles</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','adminManageRolesModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-1 space-y-3">
                    <label class="block text-sm font-medium text-gray-700" for="adminRoleName">Role Type</label>
                    <input type="text" id="adminRoleName" class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm" placeholder="e.g. Driver, Usher" />
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'adminSaveRole','variant' => 'primary','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'adminSaveRole','variant' => 'primary','class' => 'w-full']); ?>Save <?php echo $__env->renderComponent(); ?>
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
                <div class="md:col-span-2">
                    <div class="border rounded-xl overflow-hidden">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Role Type</th>
                                    <th class="px-4 py-3 text-center w-20">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminRolesTableBody">
                                <?php if (isset($component)) { $__componentOriginal112d5e476c92fff12a90a4ecc845ba67 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal112d5e476c92fff12a90a4ecc845ba67 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table-loading-state','data' => ['colspan' => '2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('table-loading-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '2']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'adminManpowerApproveModal','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'adminManpowerApproveModal','maxWidth' => '2xl']); ?>
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Validate Manpower Request</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','adminManpowerApproveModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                    <div class="text-gray-500 uppercase text-xs">Requester</div>
                    <div class="font-semibold text-gray-900" data-approve-field="user">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Role</div>
                    <div class="font-semibold text-gray-900" data-approve-field="role">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Qty Requested</div>
                    <div class="font-semibold text-gray-900" data-approve-field="quantity">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Schedule</div>
                    <div class="font-semibold text-gray-900" data-approve-field="schedule">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Municipality</div>
                    <div class="font-semibold text-gray-900" data-approve-field="municipality">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Barangay</div>
                    <div class="font-semibold text-gray-900" data-approve-field="barangay">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Purpose</div>
                    <div class="font-medium text-gray-900" data-approve-field="purpose">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Specific Area</div>
                    <div class="font-medium text-gray-900" data-approve-field="location">—</div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Approved Quantity</label>
                <input type="number" min="1" id="adminApprovedQuantity" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Cannot exceed requested quantity.</p>
            </div>
            <div class="border-t pt-4">
                <div class="text-sm text-gray-500 uppercase mb-2">Letter</div>
                <div data-approve-field="letter" class="text-sm text-gray-700">No letter uploaded.</div>
            </div>
            <div class="flex justify-end gap-3">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','@click' => '$dispatch(\'close-modal\',\'adminManpowerApproveModal\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','@click' => '$dispatch(\'close-modal\',\'adminManpowerApproveModal\')']); ?>Cancel <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'confirmAdminApproval','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'confirmAdminApproval','variant' => 'success']); ?>Confirm Approval <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'adminManpowerViewModal','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'adminManpowerViewModal','maxWidth' => '2xl']); ?>
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Request Details</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','adminManpowerViewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                    <div class="text-gray-500 uppercase text-xs">Requester</div>
                    <div class="font-semibold text-gray-900" data-view-field="user">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Status</div>
                    <div class="font-semibold text-gray-900" data-view-field="status">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Role</div>
                    <div class="font-semibold text-gray-900" data-view-field="role">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Qty (Approved / Requested)</div>
                    <div class="font-semibold text-gray-900" data-view-field="quantity">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Schedule</div>
                    <div class="font-semibold text-gray-900" data-view-field="schedule">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Municipality</div>
                    <div class="font-semibold text-gray-900" data-view-field="municipality">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Barangay</div>
                    <div class="font-semibold text-gray-900" data-view-field="barangay">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Purpose</div>
                    <div class="font-medium text-gray-900" data-view-field="purpose">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Specific Area</div>
                    <div class="font-medium text-gray-900" data-view-field="location">—</div>
                </div>
            </div>
            <div class="border-t pt-4">
                <div class="text-sm text-gray-500 uppercase mb-2">Letter</div>
                <div data-view-field="letter" class="text-sm text-gray-700">No letter uploaded.</div>
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
        window.ADMIN_MANPOWER = {
            list: "<?php echo e(route('admin.manpower.requests.list')); ?>",
            status: function(id){ return "<?php echo e(url('/admin/manpower-requests')); ?>/"+id+"/status"; },
            roles: {
                list: "<?php echo e(route('manpower.roles.index')); ?>",
                store: "<?php echo e(route('admin.manpower.roles.store')); ?>",
                delete: function(id){ return "<?php echo e(url('/admin/manpower-roles')); ?>/"+id; },
            },
            csrf: "<?php echo e(csrf_token()); ?>"
        };
    </script>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
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
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/manpower/index.blade.php ENDPATH**/ ?>