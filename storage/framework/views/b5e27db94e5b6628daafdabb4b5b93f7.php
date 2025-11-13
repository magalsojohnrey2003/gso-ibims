<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/logo2.png')); ?>">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <!-- Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Auth-specific CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('css/auth.css')); ?>">

    <!-- Use main app.css too to get theme variables & utility classes available on auth pages -->
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
</head>
<body>
    <?php echo $__env->yieldContent('content'); ?>
</body>
</html><?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/layouts/auth.blade.php ENDPATH**/ ?>