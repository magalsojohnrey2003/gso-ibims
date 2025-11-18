<form method="POST" action="<?php echo e(route('login')); ?>" id="loginForm" class="auth-form flex flex-col" novalidate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="auth_form" value="login">

    <h2 class="text-center md:text-left">LOGIN</h2>

    <?php $err = $errors->getBag('login')->first('email'); ?>
    <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
        <input id="login_email" type="text" name="email" value="<?php echo e(old('auth_form') === 'login' ? old('email') : ''); ?>" placeholder=" " required>
        <label for="login_email">Email</label>
        <?php if($err): ?>
            <div class="error" data-bag="login" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
        <?php else: ?>
            <div class="error" aria-hidden="true"></div>
        <?php endif; ?>
    </div>

    <?php $err = $errors->getBag('login')->first('password'); ?>
    <div class="input-field <?php echo e($err ? 'error' : ''); ?>">
        <input id="login_password" type="password" name="password" placeholder=" " required>
        <label for="login_password">Password</label>
        <span class="password-eye" data-target="#login_password"><i class="fa-solid fa-eye"></i></span>
        <?php if($err): ?>
            <div class="error" data-bag="login" data-full-error="<?php echo e($err); ?>" tabindex="0"><?php echo e($err); ?></div>
        <?php else: ?>
            <div class="error" aria-hidden="true"></div>
        <?php endif; ?>
    </div>

    <div class="form-options flex items-center justify-between mt-2">
        <label class="remember inline-flex items-center">
            <input id="login_remember" type="checkbox" name="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
            <span class="ml-2">Remember me</span>
        </label>

        <?php if(Route::has('password.request')): ?>
            <a href="<?php echo e(route('password.request')); ?>" class="forgot-link text-sm">Forgot password?</a>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-primary w-full mt-4">Log In</button>

    <div class="register mt-4 text-center">
        <p>Don't have an account? <a href="#" class="switch-text" data-target="register">Register</a></p>
    </div>
</form>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/auth/partials/login-form.blade.php ENDPATH**/ ?>