
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'primary',     // primary | secondary | danger | success
    'size' => 'md',             // sm | md | lg
    'iconPosition' => 'left',   // left | right
    'as' => 'button',           // button | a
    'href' => null,             // when as="a"
    'type' => 'button',         // button | submit | reset (when as="button")
    'disabled' => false,
    'iconName' => null,         // e.g. "trash", "pencil-square"
    'iconStyle' => 'o',         // o = outline, s = solid
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
    'variant' => 'primary',     // primary | secondary | danger | success
    'size' => 'md',             // sm | md | lg
    'iconPosition' => 'left',   // left | right
    'as' => 'button',           // button | a
    'href' => null,             // when as="a"
    'type' => 'button',         // button | submit | reset (when as="button")
    'disabled' => false,
    'iconName' => null,         // e.g. "trash", "pencil-square"
    'iconStyle' => 'o',         // o = outline, s = solid
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $variantClasses = [
        'primary'   => 'text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:ring-indigo-500 hover:shadow-[0_0_10px_#6366f1] border border-transparent',
        'secondary' => 'text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-indigo-500 hover:shadow-[0_0_10px_#9ca3af] border border-gray-300 dark:border-gray-600',
        'danger'    => 'text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:ring-red-500 hover:shadow-[0_0_10px_#ef4444] border border-transparent',
        'success'   => 'text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 focus:ring-green-500 hover:shadow-[0_0_10px_#22c55e] border border-transparent',
    ];

    $sizeClasses = [
        'sm' => 'px-2.5 py-1.5 text-sm',
        'md' => 'px-3 py-2 text-sm',
        'lg' => 'px-4 py-2.5 text-base',
    ];

    
    $base = 'inline-flex items-center justify-center rounded-md shadow-sm font-medium transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $classes = implode(' ', [
        $base,
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $variantClasses[$variant] ?? $variantClasses['primary'],
    ]);

    $tag = $as === 'a' ? 'a' : 'button';

    // Build the Heroicon component name if iconName is provided
    $iconComponent = $iconName ? "heroicon-{$iconStyle}-{$iconName}" : null;
?>

<<?php echo e($tag); ?>

    <?php if($tag === 'a'): ?> href="<?php echo e($href); ?>" <?php else: ?> type="<?php echo e($type); ?>" <?php endif; ?>
    <?php echo e($disabled ? 'disabled' : ''); ?>

    <?php echo e($attributes->merge([
        'class' => $classes,
        'data-spinner' => $attributes->get('data-spinner', 'true'),
    ])); ?>

>
    
    <?php if(($iconPosition ?? 'left') === 'left'): ?>
        <?php if(isset($icon)): ?>
            <span class="mr-2"><?php echo e($icon); ?></span>
        <?php elseif($iconComponent): ?>
            <span class="mr-2">
                <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $iconComponent] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
            </span>
        <?php endif; ?>
    <?php endif; ?>

    <span><?php echo e($slot); ?></span>

    
    <?php if(($iconPosition ?? 'left') === 'right'): ?>
        <?php if(isset($icon)): ?>
            <span class="ml-2"><?php echo e($icon); ?></span>
        <?php elseif($iconComponent): ?>
            <span class="ml-2">
                <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $iconComponent] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
            </span>
        <?php endif; ?>
    <?php endif; ?>
</<?php echo e($tag); ?>>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/components/button.blade.php ENDPATH**/ ?>