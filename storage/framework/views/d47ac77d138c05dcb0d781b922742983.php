
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'level'     => 'h1',
    'icon'      => null,
    'variant'   => 's',      // 's' solid, 'o' outline
    'size'      => '2xl',
    'weight'    => 'bold',
    'iconSize'  => '5',
    'iconColor' => 'title-purple',   // token (see mapping below)
    'iconStyle' => 'plain',        // 'plain' | 'circle'
    'iconBg'    => 'transparent',  // token used for circle bg
    'iconLabel' => null,
    // Compact mode removes bottom margin and tightens line-height for use in page title bars
    'compact'   => false,
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
    'level'     => 'h1',
    'icon'      => null,
    'variant'   => 's',      // 's' solid, 'o' outline
    'size'      => '2xl',
    'weight'    => 'bold',
    'iconSize'  => '5',
    'iconColor' => 'title-purple',   // token (see mapping below)
    'iconStyle' => 'plain',        // 'plain' | 'circle'
    'iconBg'    => 'transparent',  // token used for circle bg
    'iconLabel' => null,
    // Compact mode removes bottom margin and tightens line-height for use in page title bars
    'compact'   => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    // size & weight maps (purge-safe)
    $sizeMap = [
        'xs'   => 'text-xs',
        'sm'   => 'text-sm',
        'base' => 'text-base',
        'lg'   => 'text-lg',
        'xl'   => 'text-xl',
        '2xl'  => 'text-[1.375rem]',
        '3xl'  => 'text-[1.75rem]',
    ];
    $weightMap = [
        'normal'    => 'font-normal',
        'semibold'  => 'font-semibold',
        'bold'      => 'font-bold',
        'extrabold' => 'font-extrabold',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['2xl'];
    $weightClass = $weightMap[$weight] ?? $weightMap['bold'];

    // Wrapper and heading classes adapt when compact=true
    $wrapperClass = trim((($compact ? 'mb-0' : 'mb-6') . ' flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3'));
    $headingBase = trim("flex items-center gap-2 {$sizeClass} {$weightClass} " . ($compact ? 'leading-none' : 'leading-tight') . ' title-heading');

    // Map commonly used tokens to utility classes (safe for editor + purge)
    $colorTokenToClass = [
        'white'        => 'text-white',
        'black'        => 'text-black',
        'gov-accent'   => 'text-gov-accent',
        'title-purple' => 'text-title-purple',
        'muted'        => 'text-muted',       // optional: if you add it to css
        // allow direct tailwind tokens like 'gray-600' -> 'text-gray-600' by convention
    ];

    $bgTokenToClass = [
        'white'        => 'bg-white',
        'black'        => 'bg-black',
        'gov-accent'   => 'bg-gov-accent',
        'title-purple' => 'bg-title-purple',
        'transparent'  => 'bg-transparent',
    ];

    // helper: convert token to text class if exists or to text-{token} for simple tailwind-like tokens
    $toTextClass = function ($token) use ($colorTokenToClass) {
        if (!$token) return '';
        if (isset($colorTokenToClass[$token])) return $colorTokenToClass[$token];
        // if token looks like tailwind color (e.g. gray-600), return text-gray-600
        if (preg_match('/^[a-z]+-[0-9]{3}$/', $token)) {
            return 'text-' . $token;
        }
        // fallback: no class (editor-safe)
        return '';
    };

    $toBgClass = function ($token) use ($bgTokenToClass) {
        if (!$token) return '';
        if (isset($bgTokenToClass[$token])) return $bgTokenToClass[$token];
        if (preg_match('/^[a-z]+-[0-9]{3}$/', $token)) {
            return 'bg-' . $token;
        }
        return '';
    };

    // final classes
    $iconSizeClass = "w-{$iconSize} h-{$iconSize}";
    $iconColorClass = $toTextClass($iconColor) ?: 'text-gray-700';
    $iconBgClass = $toBgClass($iconBg) ?: 'bg-transparent';

    // wrapper classes (circle vs plain)
    if ($icon && $iconStyle === 'circle') {
        // circle: padding, rounded-full and background class
        $iconWrapperClass = trim("inline-flex title-icon items-center justify-center p-1.5 rounded-full {$iconBgClass}");
        // when circle, icon itself should use text color class
        $iconInnerClass = $iconColorClass;
    } else {
        // plain: small margin, icon colored via iconColorClass
        $iconWrapperClass = "inline-flex title-icon items-center justify-center";
        $iconInnerClass = $iconColorClass;
    }

    // heroicon component name (blade-heroicons v2 style)
    $componentName = $icon ? ('heroicon-' . ($variant === 's' ? 's' : 'o') . '-' . $icon) : null;
?>

<div <?php echo e($attributes->merge(['class' => $wrapperClass])); ?>>
    
    <<?php echo e($level); ?> class="<?php echo e($headingBase); ?> text-current">
        <?php if($icon && $componentName): ?>
            <span class="<?php echo e($iconWrapperClass); ?>" <?php if($iconLabel): ?> role="img" aria-label="<?php echo e($iconLabel); ?>" <?php else: ?> aria-hidden="true" <?php endif; ?>>
                
                <?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $componentName] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => ''.e($iconSizeClass).' '.e($iconInnerClass).' title-icon-graphic']); ?>
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

        <span class="leading-tight text-current title-heading-label">
            <?php echo e($slot); ?>

        </span>
    </<?php echo e($level); ?>>

    
    <?php if(isset($actions) && trim($actions) !== ''): ?>
        <div class="flex items-center gap-2">
            <?php echo e($actions); ?>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/components/title.blade.php ENDPATH**/ ?>