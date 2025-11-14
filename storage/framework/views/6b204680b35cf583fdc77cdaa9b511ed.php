
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Vite -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-8">
                <?php echo e($slot); ?>

            </div>
        </div>
    </div>
</body>
</html>
    <?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/layouts/guest.blade.php ENDPATH**/ ?>