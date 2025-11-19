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
            <div id="userManpowerAlert" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <?php if (isset($component)) { $__componentOriginala29c4b6de1220dbc50317dc759b47929 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29c4b6de1220dbc50317dc759b47929 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.title','data' => ['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'users','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'users','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']); ?> Request Manpower  <?php echo $__env->renderComponent(); ?>
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
                            <input type="text" id="user-manpower-search" placeholder="Search role or purpose"
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'openManpowerCreate','variant' => 'primary','iconName' => 'plus','class' => 'px-4 py-2 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'openManpowerCreate','variant' => 'primary','iconName' => 'plus','class' => 'px-4 py-2 text-sm']); ?>Add Request <?php echo $__env->renderComponent(); ?>
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
                                <th class="px-6 py-3 text-center">Request ID</th>
                                <th class="px-6 py-3 text-center">Qty (Approved / Requested)</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3 text-center">Schedule</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userManpowerTableBody" class="text-center">
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

    <template id="user-manpower-empty-state-template">
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

    <!-- Create Modal -->
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'userManpowerCreateModal','maxWidth' => '3xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'userManpowerCreateModal','maxWidth' => '3xl']); ?>
        <div x-data="{
            openSection: 1,
            setSection(idx) {
                this.openSection = (this.openSection === idx) ? null : idx;
            },
        }" class="relative h-full">
            <!-- Sticky Header -->
            <div class="sticky top-0 z-10 bg-purple-600 text-white px-6 py-4 flex items-center justify-between rounded-t-lg shadow">
                <div class="flex items-center gap-3">
                    <i class="fas fa-users text-2xl"></i>
                    <span class="text-xl font-bold">Request Manpower</span>
                </div>
                <button class="text-white hover:text-gray-200 text-2xl" @click="$dispatch('close-modal','userManpowerCreateModal')"><i class="fas fa-times"></i></button>
            </div>

            <div class="overflow-y-auto max-h-[calc(90vh-120px)] px-0">
            <form id="userManpowerForm" onsubmit="return false;" class="space-y-6 pt-4">
                <!-- Accordion 1: Personnel Requirements & Context -->
                <div class="border rounded-lg mb-2">
                    <button type="button" @click="setSection(1)" class="w-full flex justify-between items-center px-4 py-3 text-left text-lg font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-t-lg">
                        Personnel Requirements & Context
                        <span x-text="openSection === 1 ? '-' : '+'"></span>
                    </button>
                    <div x-show="openSection === 1" class="p-4 space-y-4 border-t">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Manpower quantity</label>
                                <input type="number" min="1" max="999" id="mp_quantity" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" oninput="if(this.value.length>3)this.value=this.value.slice(0,3);" placeholder="Max 999" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Manpower role</label>
                                <select id="mp_role" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                    <option value="">Loading roles...</option>
                                </select>
                                <p id="mp_role_empty" class="text-xs text-red-500 mt-1 hidden">No roles available. Please contact the admin.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Purpose</label>
                                <input type="text" id="mp_purpose" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Brief purpose of the request" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Request Office/Agency</label>
                                <input type="text" id="mp_office" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Optional" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accordion 2: Location and Schedule -->
                <div class="border rounded-lg mb-2">
                    <button type="button" @click="setSection(2)" class="w-full flex justify-between items-center px-4 py-3 text-left text-lg font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-t-lg">
                        Location and Schedule
                        <span x-text="openSection === 2 ? '-' : '+'"></span>
                    </button>
                    <div x-show="openSection === 2" class="p-4 space-y-4 border-t">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Municipality / City</label>
                                <select id="mp_municipality" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                    <option value="">Select municipality</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Barangay</label>
                                <select id="mp_barangay" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                    <option value="">Select barangay</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Specific area (Purok/Zone/Sitio)</label>
                                <input type="text" id="mp_location" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="e.g. Purok 3, Zone 2" />
                            </div>
                        </div>
                        <div class="mt-2 mb-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Location Preview</label>
                            <div id="locationPreview" class="text-sm text-gray-700 bg-gray-100 rounded px-3 py-2"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 border rounded-xl">
                                <h4 class="font-semibold text-gray-900 text-sm mb-3">Start Schedule</h4>
                                <label class="block text-xs font-semibold text-gray-600">Date</label>
                                <input type="date" id="mp_start_date" class="mt-1 mb-3 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                                <label class="block text-xs font-semibold text-gray-600">Time (optional)</label>
                                <input type="time" id="mp_start_time" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                            </div>
                            <div class="p-4 border rounded-xl">
                                <h4 class="font-semibold text-gray-900 text-sm mb-3">End Schedule</h4>
                                <label class="block text-xs font-semibold text-gray-600">Date</label>
                                <input type="date" id="mp_end_date" class="mt-1 mb-3 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                                <label class="block text-xs font-semibold text-gray-600">Time (optional)</label>
                                <input type="time" id="mp_end_time" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                            </div>
                        </div>
                        <div class="mt-2 mb-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Schedule Preview</label>
                            <div id="schedulePreview" class="text-sm text-gray-700 bg-gray-100 rounded px-3 py-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Accordion 3: Supporting Documents -->
                <div class="border rounded-lg mb-2">
                    <button type="button" @click="setSection(3)" class="w-full flex justify-between items-center px-4 py-3 text-left text-lg font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-t-lg">
                        Supporting Documents
                        <span x-text="openSection === 3 ? '-' : '+'"></span>
                    </button>
                    <div x-show="openSection === 3" class="p-4 space-y-4 border-t">
                        <label class="block text-sm font-medium text-gray-700">Letter (PDF/JPG/PNG, max 5MB)</label>
                        <input type="file" id="mp_letter" name="letter_file" accept="application/pdf,image/png,image/jpeg" class="filepond mt-1 block w-full text-sm" />
                        <div class="text-xs text-gray-500 mt-1">Accepted: PDF, JPG, PNG. Max size: 5MB.</div>
                    </div>
                </div>

                <!-- Sticky Footer -->
                <div class="sticky bottom-0 left-0 right-0 z-10 bg-white border-t px-6 py-4 flex justify-end gap-3 rounded-b-lg shadow">
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'saveManpowerRequest','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'saveManpowerRequest','variant' => 'success']); ?>Review & Submit <?php echo $__env->renderComponent(); ?>
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
            </div>
            <!-- FilePond CSS/JS -->
            <link href="https://unpkg.com/filepond@^4/dist/filepond.min.css" rel="stylesheet" />
            <script src="https://unpkg.com/filepond@^4/dist/filepond.min.js"></script>
            <script src="https://unpkg.com/filepond-plugin-file-validate-type@^1/dist/filepond-plugin-file-validate-type.min.js"></script>
            <script src="https://unpkg.com/filepond-plugin-file-validate-size@^2/dist/filepond-plugin-file-validate-size.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Restrict date pickers to today or future
                var today = new Date().toISOString().split('T')[0];
                document.getElementById('mp_start_date').setAttribute('min', today);
                document.getElementById('mp_end_date').setAttribute('min', today);

                // FilePond setup
                FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
                FilePond.create(document.getElementById('mp_letter'), {
                    labelIdle: 'Drag & Drop your letter or <span class="filepond--label-action">Browse</span>',
                    acceptedFileTypes: ['application/pdf', 'image/png', 'image/jpeg'],
                    maxFileSize: '5MB',
                    allowMultiple: false,
                    name: 'letter_file',
                    credits: false
                });

                // Location Preview logic
                function updateLocationPreview() {
                    var muni = document.getElementById('mp_municipality');
                    var brgy = document.getElementById('mp_barangay');
                    var area = document.getElementById('mp_location');
                    var txt = '';
                    if (muni && muni.value) txt += muni.options[muni.selectedIndex].text;
                    if (brgy && brgy.value) txt += (txt ? ', ' : '') + brgy.options[brgy.selectedIndex].text;
                    if (area && area.value) txt += (txt ? ', ' : '') + area.value;
                    document.getElementById('locationPreview').textContent = txt || '—';
                }
                document.getElementById('mp_municipality').addEventListener('change', updateLocationPreview);
                document.getElementById('mp_barangay').addEventListener('change', updateLocationPreview);
                document.getElementById('mp_location').addEventListener('input', updateLocationPreview);
                updateLocationPreview();

                // Schedule Preview logic
                function updateSchedulePreview() {
                    var sd = document.getElementById('mp_start_date').value;
                    var st = document.getElementById('mp_start_time').value;
                    var ed = document.getElementById('mp_end_date').value;
                    var et = document.getElementById('mp_end_time').value;
                    var txt = '';
                    if (sd) txt += 'Start: ' + sd + (st ? (' ' + st) : '');
                    if (ed) txt += (txt ? ' | ' : '') + 'End: ' + ed + (et ? (' ' + et) : '');
                    document.getElementById('schedulePreview').textContent = txt || '—';
                }
                document.getElementById('mp_start_date').addEventListener('change', updateSchedulePreview);
                document.getElementById('mp_start_time').addEventListener('input', updateSchedulePreview);
                document.getElementById('mp_end_date').addEventListener('change', updateSchedulePreview);
                document.getElementById('mp_end_time').addEventListener('input', updateSchedulePreview);
                updateSchedulePreview();
            });
            </script>
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

    <!-- Confirmation Modal -->
    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'userManpowerConfirmModal','maxWidth' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'userManpowerConfirmModal','maxWidth' => 'sm']); ?>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Submit Manpower Request?</h3>
            <p class="text-sm text-gray-600">Please confirm you want to submit this request.</p>
            <div class="flex justify-end gap-3">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['variant' => 'secondary','@click' => '$dispatch(\'close-modal\',\'userManpowerConfirmModal\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','@click' => '$dispatch(\'close-modal\',\'userManpowerConfirmModal\')']); ?>Cancel <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['id' => 'confirmManpowerSubmit','variant' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'confirmManpowerSubmit','variant' => 'primary']); ?>Submit <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'userManpowerViewModal','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'userManpowerViewModal','maxWidth' => '2xl']); ?>
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Request Details</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','userManpowerViewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                    <div class="text-gray-500 uppercase text-xs">Request ID</div>
                    <div class="font-semibold text-gray-900" data-user-view="id">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Status</div>
                    <div class="font-semibold text-gray-900" data-user-view="status">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Role</div>
                    <div class="font-semibold text-gray-900" data-user-view="role">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Qty (Approved / Requested)</div>
                    <div class="font-semibold text-gray-900" data-user-view="quantity">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Municipality</div>
                    <div class="font-semibold text-gray-900" data-user-view="municipality">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Barangay</div>
                    <div class="font-semibold text-gray-900" data-user-view="barangay">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Schedule</div>
                    <div class="font-semibold text-gray-900" data-user-view="schedule">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Purpose</div>
                    <div class="font-medium text-gray-900" data-user-view="purpose">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Specific Area</div>
                    <div class="font-medium text-gray-900" data-user-view="location">—</div>
                </div>
            </div>
            <div class="border-t pt-4 flex flex-col md:flex-row gap-6">
                <div class="flex-1">
                    <div class="text-sm text-gray-500 uppercase mb-2">Letter</div>
                    <div data-user-view="letter" class="text-sm text-gray-700">No letter uploaded.</div>
                </div>
                <div class="flex-1 text-center">
                    <div class="text-sm text-gray-500 uppercase mb-2">QR Status</div>
                    <div id="userManpowerQr" class="flex items-center justify-center" style="min-height: 160px;">
                        <div class="text-sm text-gray-400">QR code unavailable.</div>
                    </div>
                    <a data-user-view="public-url" href="#" target="_blank" class="text-indigo-600 hover:underline text-sm mt-2 hidden">Open status page</a>
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

    <div id="userManpowerBadgeTemplates" class="hidden">
        <template data-status="pending"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
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
        <template data-status="approved"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
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
        <template data-status="rejected"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
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
        <template data-status="default"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'gray','text' => '—']); ?>
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
    </div>

    <script>
        window.USER_MANPOWER = {
            list: "<?php echo e(route('user.manpower.list')); ?>",
            store: "<?php echo e(route('user.manpower.store')); ?>",
            print: "<?php echo e(route('user.manpower.print', '__ID__')); ?>",
            roles: "<?php echo e(route('manpower.roles.index')); ?>",
            locations: {
                municipalities: "<?php echo e(route('api.locations.municipalities')); ?>",
                barangays: "<?php echo e(url('api/locations/barangays')); ?>",
            },
            csrf: "<?php echo e(csrf_token()); ?>",
        };
        window.renderUserManpowerBadge = function(status){
            const tpl = document.querySelector(`#userManpowerBadgeTemplates template[data-status="${(status||'').toLowerCase()}"]`) || document.querySelector('#userManpowerBadgeTemplates template[data-status="default"]');
            return tpl ? tpl.innerHTML : status;
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
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/user/manpower/index.blade.php ENDPATH**/ ?>