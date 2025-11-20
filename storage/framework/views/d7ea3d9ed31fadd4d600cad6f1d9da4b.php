<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'type' => 'info',   // success | info | warning | error
    'message' => null,  // single message
    'title' => null,    // optional title
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'type' => 'info',   // success | info | warning | error
    'message' => null,  // single message
    'title' => null,    // optional title
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $styles = [
        'success' => 'bg-green-50 text-green-800 border-green-200',
        'info'    => 'bg-blue-50 text-blue-800 border-blue-200',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'error'   => 'bg-red-50 text-red-800 border-red-200',
    ];
    $icons = [
        'success' => 'fa-solid fa-check-circle',       
        'info'    => 'fa-solid fa-info-circle',       
        'warning' => 'fa-solid fa-exclamation-triangle', 
        'error'   => 'fa-solid fa-times-circle',       
    ];
    $classes = $styles[$type] ?? $styles['info'];
    $icon = $icons[$type] ?? $icons['info'];
?>

<div 
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4000)" 
    x-transition:enter="transform transition ease-out duration-500"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-400"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
    class="fixed top-4 right-4 w-96 rounded-xl border p-4 shadow-lg flex items-start gap-3 z-[9999] <?php echo e($classes); ?>"
>
    <i class="<?php echo e($icon); ?> text-lg mt-0.5"></i>
    <div class="flex-1 space-y-1">
        <?php if($title): ?>
            <div class="font-semibold"><?php echo e($title); ?></div>
        <?php endif; ?>

        
        <?php if($message): ?>
            <div class="text-sm leading-relaxed"><?php echo e($message); ?></div>
        <?php endif; ?>

        
        <?php if($errors->any() && $type === 'error'): ?>
            <ul class="list-disc list-inside text-sm space-y-1">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>

        <?php echo e($slot); ?>

    </div>

    
    <button @click="show = false" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors duration-200">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/components/alert.blade.php ENDPATH**/ ?>