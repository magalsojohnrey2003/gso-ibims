<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'colspan' => 1,
    'title' => 'No Records Found',
    'description' => 'There are no records to display at this time.',
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
    'colspan' => 1,
    'title' => 'No Records Found',
    'description' => 'There are no records to display at this time.',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<tr <?php echo e($attributes->class('bg-white')); ?>>
    <td colspan="<?php echo e((int) $colspan); ?>" class="table-state-cell text-center py-10 px-4">
        <div class="flex flex-col items-center justify-center text-center text-gray-500">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-7 4h8m-9 4h10m2 4H5a2 2 0 0 1-2-2V7c0-1.1.9-2 2-2h14a2 2 0 0 1 2 2v8c0 1.1-.9 2-2 2z" />
                </svg>
            </span>
            <p class="mt-4 text-base font-semibold text-gray-800"><?php echo e($title); ?></p>
            <p class="text-sm text-gray-500"><?php echo e($description); ?></p>
        </div>
    </td>
</tr>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/components/table-empty-state.blade.php ENDPATH**/ ?>