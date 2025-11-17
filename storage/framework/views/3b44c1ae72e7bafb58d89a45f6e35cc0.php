<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSO-IBIMS | Welcome</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen text-white" style="background-color:#3F1D7B;background-image:url('<?php echo e(asset('images/landing-bg.svg')); ?>');background-size:cover;background-position:center;background-repeat:no-repeat;">
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-20 flex flex-col lg:flex-row items-center gap-16">
            <div class="flex-1 space-y-8">
                <span class="inline-flex items-center px-4 py-1 rounded-full bg-white/10 uppercase tracking-wide text-xs font-semibold">
                    Government Services - IBIMS
                </span>
                <h1 class="text-4xl md:text-5xl font-bold leading-tight">
                    Streamline Your General Services Operations.
                </h1>
                <p class="text-lg text-white/90 leading-relaxed max-w-xl">
                    Efficiently manage item borrowing and manpower requests for government offices.
                    Automate encoding, track real-time availability, and keep every transaction auditable.
                    GSO-IBIMS keeps accountability, transparency, and citizen service at the center of your daily work.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="<?php echo e(route('login')); ?>"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold bg-blue-500 text-white shadow-lg hover:bg-blue-600 hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        Get Started
                    </a>
                    <a href="<?php echo e(route('public.borrow-items')); ?>"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold bg-white/15 text-white shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        View Features
                    </a>
                </div>

                <?php if($featuredItems->isNotEmpty()): ?>
                    <div class="bg-white/10 border border-white/10 rounded-2xl p-6 backdrop-blur">
                        <p class="uppercase text-xs tracking-wide mb-3 text-white/70">Recently Added Assets</p>
                        <div class="flex flex-wrap gap-4">
                            <?php $__currentLoopData = $featuredItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo e($item->photo_url); ?>" alt="<?php echo e($item->name); ?>" class="h-12 w-12 rounded-lg object-cover bg-white/10">
                                    <div>
                                        <p class="font-semibold"><?php echo e($item->name); ?></p>
                                        <p class="text-xs text-white/70"><?php echo e($item->category_name); ?> - <?php echo e($item->total_qty); ?> total</p>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- <div class="flex-1 w-full flex justify-center">
                <div class="relative bg-white/5 border border-white/20 rounded-[2.5rem] h-[32rem] w-full max-w-sm lg:max-w-lg overflow-hidden shadow-2xl">
                    <div id="landingPageImagePlaceholder" class="w-full h-full flex items-center justify-center text-white/70 text-center px-6">
                        <img src="<?php echo e(asset('images/mayor.png')); ?>" class="h-full w-full center object-cover" alt="GSO Mobile">
                    </div>
                </div>
            </div> -->
            
        </div>
    </div>
</body>
</html>



<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/landing.blade.php ENDPATH**/ ?>