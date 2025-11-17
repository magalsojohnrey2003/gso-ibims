
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['user' => null, 'action' => '#', 'method' => 'POST']));

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

foreach (array_filter((['user' => null, 'action' => '#', 'method' => 'POST']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<style>
/* Floating label styles for user modal */
.user-form-field {
    position: relative;
    margin: 16px 0;
}
.user-form-field input {
    width: 100%;
    min-height: 48px;
    background: #f9f9f9;
    border: 1px solid #ccc;
    border-radius: 6px;
    outline: none;
    font-size: 1rem;
    color: #1E1E2F;
    padding: 14px;
    padding-right: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-sizing: border-box;
}
.user-form-field input:focus {
    border-color: #A855F7;
    box-shadow: 0 0 4px rgba(168, 85, 247, 0.4);
}
.user-form-field label {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #666;
    font-size: 0.9rem;
    pointer-events: none;
    transition: 0.14s ease;
    background: #f9f9f9;
    padding: 0 6px;
}
.user-form-field input:focus ~ label,
.user-form-field input:not(:placeholder-shown) ~ label,
.user-form-field input.has-value ~ label {
    top: -8px;
    font-size: 0.75rem;
    color: #A855F7;
    transform: none;
}
.user-form-field.has-password-eye input {
    padding-right: 46px;
}
.user-form-field .password-eye {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    cursor: pointer;
    color: #A855F7;
    font-size: 1.1rem;
    z-index: 2;
}
.user-form-field .password-eye:hover {
    color: #7e22ce;

/* Validation states */
.user-form-field.error input {
    border-color: #D32F2F;
}
.user-form-field.error label {
    color: #D32F2F;
}
.user-form-field.success input {
    border-color: #2E7D32;
}
.user-form-field.success label {
    color: #2E7D32;
}
.user-form-field .error {
    color: #D32F2F;
    font-size: 0.78rem;
    line-height: 1.25;
    position: absolute;
    top: calc(100% + 4px);
    left: 12px;
    right: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: help;
    margin: 0;
    padding: 0;
    background: transparent;
    z-index: 3;
}
}
</style>

<form method="POST" action="<?php echo e($action); ?>" id="user-form" <?php if(!empty($ajax)): ?> data-ajax="true" <?php endif; ?> class="modern-user-form">
    <?php echo csrf_field(); ?>
    <?php if(strtoupper($method) !== 'POST'): ?>
        <?php echo method_field($method); ?>
    <?php endif; ?>

    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="user-form-field">
                <input name="first_name" 
                       value="<?php echo e(old('first_name', $user->first_name ?? '')); ?>" 
                       placeholder=" "
                       required />
                <label>First name *</label>
                <div class="error" aria-hidden="true"></div>
            </div>
            <div class="user-form-field">
                <input name="middle_name" 
                       value="<?php echo e(old('middle_name', $user->middle_name ?? '')); ?>" 
                       placeholder=" " />
                <label>Middle name</label>
                <div class="error" aria-hidden="true"></div>
            </div>
            <div class="user-form-field">
                <input name="last_name" 
                       value="<?php echo e(old('last_name', $user->last_name ?? '')); ?>" 
                       placeholder=" "
                       required />
                <label>Last name *</label>
                <div class="error" aria-hidden="true"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="user-form-field">
                <input type="email"
                       name="email" 
                       value="<?php echo e(old('email', $user->email ?? '')); ?>" 
                       placeholder=" "
                       required />
                <label>Email *</label>
                <div class="error" aria-hidden="true"></div>
            </div>

            <div class="user-form-field has-password-eye">
                <input type="password" 
                       name="password" 
                       id="user-password-field"
                       placeholder=" "
                       <?php echo e($user ? '' : 'required'); ?> />
                <label>Password <?php echo e($user ? '' : '*'); ?></label>
                <span class="password-eye" data-target="#user-password-field">
                    <i class="fa-solid fa-eye"></i>
                </span>
                <div class="error" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div id="form-errors" class="text-red-600 text-sm mb-3 bg-red-50 p-3 rounded-lg hidden"></div>
        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
            <?php echo e($user ? 'Save changes' : 'Create User'); ?>

        </button>
    </div>
</form>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/users/_form.blade.php ENDPATH**/ ?>