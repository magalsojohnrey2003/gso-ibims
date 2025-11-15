<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSO-IBIMS | Explore Items</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-gray-50 min-h-screen text-gray-900">
    <header class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                    <img src="<?php echo e(asset('images/logo2.png')); ?>" alt="GSO-IBIMS Logo" class="h-12 w-auto object-contain"><div>
                    <p class="text-lg font-semibold">GSO-IBIMS</p>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Borrow Items Preview</p>
                </div>
            </div>
            <a href="<?php echo e(route('login')); ?>" class="px-5 py-2 rounded-full bg-purple-600 text-white font-semibold hover:bg-purple-700 transition">
                Log in / Register
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-12 space-y-12">
        <section class="space-y-4">
            <h1 class="text-3xl font-bold text-gray-900">Browse Available Assets</h1>
            <p class="text-gray-600 max-w-3xl">
                This read-only view mirrors what authenticated staff members see when reserving resources.
                Sign in to borrow items, queue manpower requests, and track fulfillment in real time.
            </p>
        </section>

        <section class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col">
                    <img src="<?php echo e($item->photo_url); ?>" alt="<?php echo e($item->name); ?>" class="h-40 w-full object-cover rounded-xl mb-4">
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold"><?php echo e($item->name); ?></h2>
                            <?php if($item->created_at && $item->created_at->isToday()): ?>
                                <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-700 font-semibold">New</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500"><?php echo e($item->category_name); ?></p>
                        <div class="flex items-center gap-4 text-sm font-medium text-gray-700 mt-2">
                            <span>Total: <?php echo e($item->total_qty); ?></span>
                            <span>Available: <?php echo e($item->available_qty); ?></span>
                        </div>
                        <?php if(filled($item->description)): ?>
                            <p class="text-sm text-gray-600 mt-3 line-clamp-3"><?php echo e($item->description); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo e(route('login')); ?>"
                       class="mt-6 inline-flex items-center justify-center px-4 py-2 rounded-xl border border-purple-200 text-purple-700 font-semibold hover:bg-purple-50 transition">
                        Log in to Borrow
                    </a>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-span-full bg-white rounded-2xl border border-dashed border-gray-300 py-16 text-center text-gray-500">
                    No items are available at the moment. Please check back soon.
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/public/borrow-items.blade.php ENDPATH**/ ?>