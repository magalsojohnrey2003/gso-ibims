<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([ 
    'type' => 'info',   // success | info | warning | danger | gray | accepted | rejected | pending
    'text' => '',
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
    'type' => 'info',   // success | info | warning | danger | gray | accepted | rejected | pending
    'text' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    // Define styles with modern colors
    $styles = [
        'success'  => 'bg-green-100 text-green-800 ring-green-200',
        'info'     => 'bg-blue-100 text-blue-800 ring-blue-200',
        'warning'  => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'danger'   => 'bg-red-100 text-red-800 ring-red-200',
        'gray'     => 'bg-gray-100 text-gray-800 ring-gray-200',
        'accepted' => 'bg-green-100 text-green-800 ring-green-200',
        'rejected' => 'bg-red-100 text-red-800 ring-red-200',
        'pending'  => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'qr'       => 'bg-teal-100 text-teal-800 ring-teal-200',
    ];

    // Define icons for each status type
    $icons = [
        'success'  => 'fa-check-circle',
        'info'     => 'fa-info-circle',
        'warning'  => 'fa-clock',
        'danger'   => 'fa-exclamation-triangle',
        'gray'     => 'fa-question-circle',
        'accepted' => 'fa-check-circle',
        'rejected' => 'fa-times-circle',
        'pending'  => 'fa-clock',
        'qr'       => 'fa-qrcode',
    ];

    // Determine which style and icon to use
    $classes = $styles[$type] ?? $styles['info'];
    $icon = $icons[$type] ?? $icons['info'];
?>

<span <?php echo e($attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset $classes"])); ?>>
    <i class="fas <?php echo e($icon); ?> text-xs"></i>
    <span><?php echo e($text ?: ucfirst($type)); ?></span>
</span>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/components/status-badge.blade.php ENDPATH**/ ?>