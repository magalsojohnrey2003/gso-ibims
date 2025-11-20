<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'colspan' => 1,
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
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<tr
    wire:loading.delay.longer
    <?php echo e($attributes->class('bg-white/80 transition-opacity duration-150')); ?>

>
    <td colspan="<?php echo e((int) $colspan); ?>" class="table-state-cell text-center py-10 px-4">
        <div class="flex flex-col items-center justify-center text-center text-gray-500">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-purple-50 text-purple-500">
                <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"></circle>
                    <path class="opacity-75" d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                </svg>
            </span>
            <p class="mt-4 text-base font-semibold text-gray-700">Loading records...</p>
            <p class="text-sm text-gray-500">Please wait while we fetch the latest data.</p>
        </div>
    </td>
</tr>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/components/table-loading-state.blade.php ENDPATH**/ ?>