
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
    <div class="p-6">

        

        
        <div class="w-full mb-6">
            <div class="flex flex-col md:flex-row items-center gap-3">
                <!-- Live Search Input -->
                <div class="relative flex-1 w-full">
                    <input 
                        type="text" 
                        id="liveSearch"
                        placeholder="Search by Name..." 
                        class="w-full px-4 py-2.5 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        autocomplete="off"
                    />
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>

                <!-- Sort by Category -->
                <select 
                    id="categoryFilter" 
                    class="px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 w-full md:w-auto min-w-[180px]"
                >
                    <option value="">All Categories</option>
                    <?php
                        $categories = $items->pluck('category_name')->unique()->sort()->values();
                    ?>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($category); ?>"><?php echo e($category); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>

                <!-- Sort by Availability -->
                <select 
                    id="availabilityFilter" 
                    class="px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 w-full md:w-auto min-w-[180px]"
                >
                    <option value="">All Items</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>

                <!-- Borrow List Icon Button -->
                <div class="flex-shrink-0 relative">
                    <a href="<?php echo e(route('borrowList.index')); ?>"
                        class="relative inline-flex items-center justify-center text-purple-600 hover:text-purple-700 transition-all hover:scale-105"
                        title="View Borrow List">
                        
                        <i class="fas fa-box-open text-3xl"></i> 
                        
                        <?php if($borrowListCount > 0): ?>
                            <span class="absolute -top-2 -right-2 inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full bg-blue-500 text-white text-xs font-bold">
                                <?php echo e($borrowListCount); ?>

                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 mt-4">
            <?php if (isset($component)) { $__componentOriginala29c4b6de1220dbc50317dc759b47929 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29c4b6de1220dbc50317dc759b47929 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.title','data' => ['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'archive-box-arrow-down','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'archive-box-arrow-down','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent']); ?>
                Borrow Items
             <?php echo $__env->renderComponent(); ?>
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

        
        <div id="itemsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div
                    class="borrow-item-card bg-white rounded-lg shadow p-4 flex flex-col"
                    data-name="<?php echo e(strtolower($item->name)); ?>"
                    data-category="<?php echo e($item->category_name); ?>"
                    data-available="<?php echo e($item->available_qty > 0 ? 'true' : 'false'); ?>"
                >

                    <!-- Item Image -->
                    <?php
                        $photoUrl = null;
                        if ($item->photo) {
                            // Check if photo is in storage (public disk)
                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($item->photo)) {
                                $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($item->photo);
                            } 
                            // Check if it's a full HTTP URL
                            elseif (str_starts_with($item->photo, 'http')) {
                                $photoUrl = $item->photo;
                            } 
                            // Check if it's in public directory (default photo or legacy path)
                            elseif (file_exists(public_path($item->photo))) {
                                $photoUrl = asset($item->photo);
                            }
                        }
                        // Use default photo if no photo found or photo column is empty
                        if (!$photoUrl) {
                            $photoUrl = asset($defaultPhoto);
                        }
                    ?>
                    <div class="relative">
                        <img src="<?php echo e($photoUrl); ?>"
                             alt="<?php echo e($item->name); ?>" 
                             class="h-32 w-full object-cover rounded border-2 border-purple-500 mb-3">
                        <?php if($item->is_new ?? false): ?>
                            <span class="absolute top-2 right-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg animate-pulse">
                                NEW
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Name + Info Icon -->
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo e($item->name); ?></h3>
                        <div class="relative group">
                            <button type="button" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-purple-100 hover:bg-purple-200 text-purple-600 transition-colors cursor-help">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <!-- Tooltip -->
                            <div class="invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200 absolute left-1/2 -translate-x-1/2 bottom-full mb-2 w-64 p-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg z-10 pointer-events-none">
                                <div class="space-y-2">
                                    <?php if($item->description): ?>
                                        <div>
                                            <p class="font-semibold text-purple-300">Description:</p>
                                            <p class="text-gray-200"><?php echo e($item->description); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-semibold text-purple-300">Category:</p>
                                        <p class="text-gray-200"><?php echo e($item->category_name); ?></p>
                                    </div>
                                </div>
                                <!-- Arrow -->
                                <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quantities -->
                    <?php
                        $isAvailable = ($item->available_qty ?? 0) > 0;
                        $iconClass = 'w-4 h-4 ' . ($isAvailable ? 'text-green-600' : 'text-red-600');
                        $availTextClass = $isAvailable ? 'text-green-700' : 'text-red-700';
                    ?>

                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="flex items-center gap-1 text-gray-700">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-archive-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 text-purple-600']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            Total: <span class="font-medium"><?php echo e($item->total_qty); ?></span>
                        </span>

                        <span class="flex items-center gap-1" title="Currently available">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-check-circle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => ''.e($iconClass).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                            Avail:
                            <span class="font-medium <?php echo e($availTextClass); ?>">
                                <?php echo e($item->available_qty); ?>

                            </span>
                        </span>
                    </div>

                    <!-- Borrow Form -->
                    <form action="<?php echo e(route('borrowList.add', $item->id)); ?>"
                        method="POST"
                        class="mt-auto borrow-add-form"
                        data-item-id="<?php echo e($item->id); ?>"
                        data-item-name="<?php echo e($item->name); ?>"
                        data-item-total="<?php echo e($item->total_qty); ?>">
                        <?php echo csrf_field(); ?>

                        
                        <div class="flex items-center space-x-2 mb-2 qty-control" data-item-max="<?php echo e($item->total_qty); ?>">
                            <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','class' => 'btn-step-down']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','class' => 'btn-step-down']); ?>- <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>

                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['type' => 'number','name' => 'qty','value' => '1','min' => '1','max' => ''.e($item->total_qty).'','class' => 'w-16 text-center qty-input']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','name' => 'qty','value' => '1','min' => '1','max' => ''.e($item->total_qty).'','class' => 'w-16 text-center qty-input']); ?>
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

                            <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','class' => 'btn-step-up','dataMax' => ''.e($item->total_qty).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','class' => 'btn-step-up','data-max' => ''.e($item->total_qty).'']); ?>+ <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                        </div>

                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full']); ?>
                            <i class="fas fa-plus-circle mr-1"></i> Add to List
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const QTY_ERROR_CLASSES = ['ring-2', 'ring-red-300', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-300'];
            const QTY_SUCCESS_CLASSES = ['ring-2', 'ring-green-300', 'border-green-500', 'focus:border-green-500', 'focus:ring-green-300'];

            // ===== Live Search and Filtering =====
            const searchInput = document.getElementById('liveSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const availabilityFilter = document.getElementById('availabilityFilter');
            const itemCards = document.querySelectorAll('.borrow-item-card');
            const itemsGrid = document.getElementById('itemsGrid');

            function filterItems() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedCategory = categoryFilter.value;
                const selectedAvailability = availabilityFilter.value;

                let visibleCount = 0;

                itemCards.forEach(card => {
                    const itemName = card.getAttribute('data-name') || '';
                    const itemCategory = card.getAttribute('data-category') || '';
                    const itemAvailable = card.getAttribute('data-available') === 'true';

                    // Check search term
                    const matchesSearch = itemName.includes(searchTerm);

                    // Check category filter
                    const matchesCategory = !selectedCategory || itemCategory === selectedCategory;

                    // Check availability filter
                    let matchesAvailability = true;
                    if (selectedAvailability === 'available') {
                        matchesAvailability = itemAvailable;
                    } else if (selectedAvailability === 'unavailable') {
                        matchesAvailability = !itemAvailable;
                    }

                    // Show or hide card
                    if (matchesSearch && matchesCategory && matchesAvailability) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Show "no results" message if needed
                let noResultsMsg = document.getElementById('noResultsMessage');
                if (visibleCount === 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'noResultsMessage';
                        noResultsMsg.className = 'col-span-full text-center py-12';
                        noResultsMsg.innerHTML = `
                            <div class="text-gray-400">
                                <i class="fas fa-search text-4xl mb-3"></i>
                                <p class="text-lg font-semibold">No items found</p>
                                <p class="text-sm">Try adjusting your search or filters</p>
                            </div>
                        `;
                        itemsGrid.appendChild(noResultsMsg);
                    }
                    noResultsMsg.style.display = '';
                } else if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }

            // Add event listeners for live filtering
            searchInput.addEventListener('input', filterItems);
            categoryFilter.addEventListener('change', filterItems);
            availabilityFilter.addEventListener('change', filterItems);

            // ===== Existing Quantity and Form Logic =====

            function clearInlineError(form) {
                const existing = form.querySelector('.inline-availability-error');
                if (existing) existing.remove();
            }

            function showInlineError(form, message) {
                clearInlineError(form);
                const div = document.createElement('div');
                div.className = 'inline-availability-error mt-2 text-sm text-red-600';
                div.textContent = message;
                form.appendChild(div);
                setTimeout(() => {
                    if (div.parentNode) div.remove();
                }, 5000);
            }

            function setQuantityState(form, state, message) {
                const input = form.querySelector('input[name="qty"], .qty-input');
                if (!input) return;

                input.classList.remove(...QTY_ERROR_CLASSES, ...QTY_SUCCESS_CLASSES);

                if (state === 'error') {
                    input.classList.add(...QTY_ERROR_CLASSES);
                    if (message) {
                        showInlineError(form, message);
                    }
                    return;
                }

                if (state === 'valid') {
                    input.classList.add(...QTY_SUCCESS_CLASSES);
                }

                clearInlineError(form);
            }

            function getItemIdFromForm(form) {
                if (form.dataset && form.dataset.itemId) return form.dataset.itemId;
                try {
                    const match = form.getAttribute('action').match(/\/(\d+)(?:\/?$|\?)/);
                    if (match && match[1]) return match[1];
                } catch (error) {
                    console.warn('Failed to parse item id from action', error);
                }
                return null;
            }

            function getItemName(form) {
                return form.dataset?.itemName || 'item';
            }

            function pluralizeItem(name, count) {
                if (count === 1) return name;
                if (!name) return 'items';
                return name.endsWith('s') ? name : name + 's';
            }

            function buildAvailabilitySignature(itemId, borrowDate, returnDate, qty) {
                return [itemId, borrowDate, returnDate, qty].join('|');
            }

            async function fetchAvailability(form, qty, { force = false, signal } = {}) {
                const itemId = getItemIdFromForm(form);
                const borrowDateEl = document.getElementById('borrow_date') || document.querySelector('input[name="borrow_date"]');
                const returnDateEl = document.getElementById('return_date') || document.querySelector('input[name="return_date"]');

                const borrowDate = borrowDateEl?.value || '';
                const returnDate = returnDateEl?.value || '';

                if (!itemId || !borrowDate || !returnDate) {
                    form.dataset.availabilitySignature = '';
                    form.dataset.availabilityAvailable = '';
                    form.dataset.availabilityRemaining = '';
                    form.dataset.availabilityMessage = '';
                    return null;
                }

                const signature = buildAvailabilitySignature(itemId, borrowDate, returnDate, qty);

                if (!force && form.dataset.availabilitySignature === signature && typeof form.dataset.availabilityAvailable !== 'undefined') {
                    return {
                        signature,
                        available: form.dataset.availabilityAvailable === '1',
                        remaining: parseInt(form.dataset.availabilityRemaining || '0', 10),
                        message: form.dataset.availabilityMessage || '',
                    };
                }

                const params = new URLSearchParams({
                    borrow_date: borrowDate,
                    return_date: returnDate,
                    qty: String(Math.max(qty, 0)),
                });

                // Build correct availability endpoint for this item
                const url = `/user/availability/${encodeURIComponent(itemId)}?${params.toString()}`;
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal,
                });

                const json = await response.json().catch(() => null);

                if (!response.ok) {
                    const message = (json && json.message) ? json.message : 'Unable to check availability right now.';
                    throw new Error(message);
                }

                let available;
                let remaining = 0;
                let message = '';

                if (json && typeof json.available !== 'undefined') {
                    available = Boolean(json.available);
                    remaining = Number.isFinite(json.remaining) ? json.remaining : 0;
                    message = json.message || '';
                } else if (Array.isArray(json) && json.length) {
                    available = false;
                    message = 'This item is blocked for part of the selected range.';
                } else {
                    available = true;
                    remaining = qty;
                }

                form.dataset.availabilitySignature = signature;
                form.dataset.availabilityAvailable = available ? '1' : '0';
                form.dataset.availabilityRemaining = String(remaining);
                form.dataset.availabilityMessage = message;

                return { signature, available, remaining, message };
            }

            function scheduleAvailabilityCheck(form) {
                const qtyInput = form.querySelector('input[name="qty"], .qty-input');
                if (!qtyInput) return;

                if (form.__availabilityTimer) {
                    clearTimeout(form.__availabilityTimer);
                }
                if (form.__availabilityAbort) {
                    form.__availabilityAbort.abort();
                    form.__availabilityAbort = null;
                }

                form.__availabilityTimer = setTimeout(async () => {
                    const qty = Math.max(0, parseInt(qtyInput.value || '0', 10));
                    if (!qty) {
                        setQuantityState(form, 'idle');
                        form.dataset.availabilitySignature = '';
                        return;
                    }

                    const controller = new AbortController();
                    form.__availabilityAbort = controller;

                    try {
                        const result = await fetchAvailability(form, qty, { force: true, signal: controller.signal });
                        if (!result) {
                            setQuantityState(form, 'idle');
                            return;
                        }
                        if (result.available) {
                            setQuantityState(form, 'valid');
                        } else {
                            const remaining = Math.max(0, result.remaining ?? 0);
                            const itemName = getItemName(form);
                            const message = remaining > 0
                                ? 'You can only borrow ' + remaining + ' more ' + pluralizeItem(itemName, remaining) + ' in this date range.'
                                : 'Not enough ' + itemName + ' available in this date range.';
                            setQuantityState(form, 'error', message);
                        }
                    } catch (error) {
                        if (controller.signal.aborted) return;
                        console.error('Availability check failed', error);
                        setQuantityState(form, 'error', error.message || 'Unable to check availability right now.');
                    } finally {
                        form.__availabilityAbort = null;
                    }
                }, 300);
            }

            document.querySelectorAll('form.borrow-add-form').forEach((form) => {
                const qtyInput = form.querySelector('input[name="qty"], .qty-input');
                if (qtyInput) {
                    qtyInput.addEventListener('input', () => {
                        setQuantityState(form, 'idle');
                        scheduleAvailabilityCheck(form);
                    });
                }

                const borrowDateEl = document.getElementById('borrow_date') || document.querySelector('input[name="borrow_date"]');
                const returnDateEl = document.getElementById('return_date') || document.querySelector('input[name="return_date"]');

                [borrowDateEl, returnDateEl].forEach((field) => {
                    if (!field) return;
                    field.addEventListener('change', () => {
                        setQuantityState(form, 'idle');
                        scheduleAvailabilityCheck(form);
                    });
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"], .x-primary-button') || form.querySelector('button');
                    if (submitBtn) submitBtn.disabled = true;

                    try {
                        const qtyField = form.querySelector('input[name="qty"], .qty-input');
                        const qty = Math.max(0, parseInt(qtyField?.value || '0', 10));
                        const maxAttr = qtyField?.getAttribute('max');
                        const datasetMax = form.dataset.itemTotal ? parseInt(form.dataset.itemTotal, 10) : null;
                        const maxAllowed = maxAttr ? parseInt(maxAttr, 10) : datasetMax;
                        const itemName = getItemName(form);

                        if (!qty || qty <= 0) {
                            setQuantityState(form, 'error', 'Please enter a valid quantity.');
                            return;
                        }
                        if (Number.isFinite(maxAllowed) && qty > maxAllowed) {
                            const message = maxAllowed <= 0
                                ? 'Not enough ' + itemName + ' available right now.'
                                : 'Only ' + maxAllowed + ' ' + pluralizeItem(itemName, maxAllowed) + ' are available.';
                            setQuantityState(form, 'error', message);
                            return;
                        }

                        const availability = await fetchAvailability(form, qty, { force: true });
                        if (availability && !availability.available) {
                            const remaining = Math.max(0, availability.remaining ?? 0);
                            const message = remaining > 0
                                ? 'You can only borrow ' + remaining + ' more ' + pluralizeItem(itemName, remaining) + ' in this date range.'
                                : 'Not enough ' + itemName + ' available in this date range.';
                            setQuantityState(form, 'error', message);
                            return;
                        }

                        setQuantityState(form, availability ? 'valid' : 'idle');
                        form.submit();
                    } catch (error) {
                        console.error('Availability validation failed', error);
                        setQuantityState(form, 'error', error.message || 'Unable to check availability right now.');
                    } finally {
                        if (submitBtn) submitBtn.disabled = false;
                    }
                });

                scheduleAvailabilityCheck(form);
            });
        });
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






<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/user/borrow-items/index.blade.php ENDPATH**/ ?>