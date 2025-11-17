<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'view-item-history','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'view-item-history','maxWidth' => '2xl']); ?>
    <div class="w-full bg-white shadow-xl overflow-hidden flex flex-col max-h-[85vh]" 
         x-data="{
            instanceId: null,
            propertyNumber: '',
            events: [],
            loading: false,
            error: '',
            async openFor(id, pn) {
                this.instanceId = id;
                this.propertyNumber = pn || '';
                this.events = [];
                this.error = '';
                this.loading = true;
                try {
                    const res = await fetch(`/admin/items/instances/${id}/history`, { headers: { Accept: 'application/json' } });
                    const data = await res.json().catch(() => null);
                    if (!res.ok) throw new Error(data?.message || 'Failed to load history');
                    this.propertyNumber = data?.property_number || pn || '';
                    this.events = Array.isArray(data?.events) ? data.events : [];
                } catch (e) {
                    this.error = e?.message || 'Failed to load history';
                } finally {
                    this.loading = false;
                }
            }
         }"
         x-init="
           window.addEventListener('item-history:open', (e) => {
              const id = e.detail?.instanceId; const pn = e.detail?.propertyNumber;
              if (!id) return; 
              $dispatch('open-modal', 'view-item-history');
              $nextTick(() => openFor(id, pn));
           });
         "
    >
        <div class="bg-indigo-600 text-white px-6 py-5 sticky top-0 z-20">
            <h3 class="text-2xl font-bold flex items-center gap-2">
                <i class="fas fa-history"></i>
                <span>History</span>
            </h3>
            <p class="text-indigo-100 mt-1 text-sm leading-relaxed">
                Property Number: <strong x-text="propertyNumber || '—'"></strong>
            </p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <template x-if="loading">
                <div class="flex items-center justify-center py-16 text-gray-500">
                    <i class="fas fa-circle-notch fa-spin mr-2"></i>
                    Loading history...
                </div>
            </template>
            <template x-if="!loading && error">
                <div class="rounded-md bg-red-50 p-4 text-red-700" x-text="error"></div>
            </template>
            <template x-if="!loading && !error && events.length === 0">
                <div class="rounded-md bg-gray-50 p-4 text-gray-600">No history found for this property number.</div>
            </template>
            <div x-show="!loading && !error && events.length > 0" class="relative">
                <ol class="relative border-l-2 border-indigo-200 ml-3">
                    <template x-for="evt in events" :key="evt.id">
                        <li class="mb-6 ml-6">
                            <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-white border-2 border-indigo-300 text-indigo-600">
                                <template x-if="evt.action === 'CREATED'"><i class="fas fa-plus"></i></template>
                                <template x-if="evt.action === 'BORROWED'"><i class="fas fa-box"></i></template>
                                <template x-if="evt.action === 'RETURNED'"><i class="fas fa-arrow-left"></i></template>
                                <template x-if="evt.action === 'DAMAGED' || evt.action === 'MINOR_DAMAGE'"><i class="fas fa-exclamation-triangle"></i></template>
                                <template x-if="!['CREATED','BORROWED','RETURNED','DAMAGED','MINOR_DAMAGE'].includes(evt.action)"><i class="fas fa-info"></i></template>
                            </span>
                            <div class="bg-white border rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="evt.action"></h4>
                                    <span class="text-xs text-gray-500" x-text="evt.performed_at"></span>
                                </div>
                                <div class="mt-2 text-sm text-gray-700 space-y-1">
                                    <template x-if="evt.actor">
                                        <div><span class="font-medium">By:</span> <span x-text="evt.actor"></span></div>
                                    </template>
                                    <template x-if="evt.payload && evt.payload.borrower">
                                        <div><span class="font-medium">Borrower:</span> <span x-text="evt.payload.borrower"></span></div>
                                    </template>
                                    <template x-if="evt.payload && evt.payload.location">
                                        <div><span class="font-medium">Location:</span> <span x-text="evt.payload.location"></span></div>
                                    </template>
                                    <template x-if="evt.payload && evt.payload.return_id">
                                        <div><span class="font-medium">Return ID:</span> <span x-text="evt.payload.return_id"></span></div>
                                    </template>
                                </div>
                            </div>
                        </li>
                    </template>
                </ol>
            </div>
        </div>
        <div class="px-6 py-4 border-t bg-white sticky bottom-0 z-20 flex justify-end">
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','iconName' => 'x-mark','type' => 'button','xOn:click' => '$dispatch(\'close-modal\', \'view-item-history\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','iconName' => 'x-mark','type' => 'button','x-on:click' => '$dispatch(\'close-modal\', \'view-item-history\')']); ?>Close <?php echo $__env->renderComponent(); ?>
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
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/items/modals/history.blade.php ENDPATH**/ ?>