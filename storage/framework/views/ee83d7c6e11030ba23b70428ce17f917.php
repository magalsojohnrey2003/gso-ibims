<form method="POST" action="<?php echo e(route('register')); ?>" id="registerForm" class="auth-form flex flex-col" novalidate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="auth_form" value="register">

    <h2 class="text-center md:text-left">SIGN UP</h2>

    <div class="form-row form-row-3">
        <?php $err = $errors->getBag('register')->first('first_name'); ?>
        <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
            <input id="register_first_name" type="text" name="first_name" value="<?php echo e(old('auth_form') === 'register' ? old('first_name') : ''); ?>" required placeholder=" " pattern="[A-Za-z\s-]+">
            <label for="register_first_name">First Name</label>
            <?php if($err): ?>
                <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
            <?php else: ?>
                <div class="error" aria-hidden="true"></div>
            <?php endif; ?>
        </div>

        <?php $err = $errors->getBag('register')->first('middle_name'); ?>
        <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
            <input id="register_middle_name" type="text" name="middle_name" value="<?php echo e(old('auth_form') === 'register' ? old('middle_name') : ''); ?>" placeholder=" " pattern="[A-Za-z\s-]+">
            <label for="register_middle_name">Middle Name</label>
            <?php if($err): ?>
                <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
            <?php else: ?>
                <div class="error" aria-hidden="true"></div>
            <?php endif; ?>
        </div>

        <?php $err = $errors->getBag('register')->first('last_name'); ?>
        <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
            <input id="register_last_name" type="text" name="last_name" value="<?php echo e(old('auth_form') === 'register' ? old('last_name') : ''); ?>" required placeholder=" " pattern="[A-Za-z\s-]+">
            <label for="register_last_name">Last Name</label>
            <?php if($err): ?>
                <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
            <?php else: ?>
                <div class="error" aria-hidden="true"></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <?php $err = $errors->getBag('register')->first('phone'); ?>
        <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
            <input id="register_phone" type="tel" name="phone" value="<?php echo e(old('auth_form') === 'register' ? old('phone') : ''); ?>" placeholder=" "
                   inputmode="numeric" maxlength="14" autocomplete="tel">
            <label for="register_phone">Phone</label>
            <?php if($err): ?>
                <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
            <?php else: ?>
                <div class="error" aria-hidden="true"></div>
            <?php endif; ?>
        </div>

        <?php $err = $errors->getBag('register')->first('email'); ?>
        <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
            <input id="register_email" type="text" name="email" value="<?php echo e(old('auth_form') === 'register' ? old('email') : ''); ?>" placeholder=" ">
            <label for="register_email">Email</label>
            <?php if($err): ?>
                <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
            <?php else: ?>
                <div class="error" aria-hidden="true"></div>
            <?php endif; ?>
        </div>
    </div>

    <?php $err = $errors->getBag('register')->first('address'); ?>
    <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
        <input id="register_address" type="text" name="address" value="<?php echo e(old('auth_form') === 'register' ? old('address') : ''); ?>" placeholder=" ">
        <label for="register_address">Address</label>
        <?php if($err): ?>
            <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
        <?php else: ?>
            <div class="error" aria-hidden="true"></div>
        <?php endif; ?>
    </div>

    <?php $err = $errors->getBag('register')->first('password'); ?>
    <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
        <input id="register_password" type="password" name="password" placeholder=" ">
        <label for="register_password">Password</label>
        <span class="password-eye" data-target="#register_password"><i class="fa-solid fa-eye"></i></span>
        <?php if($err): ?>
            <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
        <?php else: ?>
            <div class="error" aria-hidden="true"></div>
        <?php endif; ?>
    </div>

    <?php $err = $errors->getBag('register')->first('password_confirmation'); ?>
    <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
        <input id="register_password_confirmation" type="password" name="password_confirmation" placeholder=" ">
        <label for="register_password_confirmation">Confirm Password</label>
        <span class="password-eye" data-target="#register_password_confirmation"><i class="fa-solid fa-eye"></i></span>
        <?php if($err): ?>
            <div class="error" data-bag="register" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
        <?php else: ?>
            <div class="error" aria-hidden="true"></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-primary w-full mt-3">Register</button>

    <div class="register mt-4 text-center">
        <p>Already have an account? <a href="#" class="switch-text" data-target="login">Login</a></p>
    </div>
</form>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/auth/partials/register-form.blade.php ENDPATH**/ ?>