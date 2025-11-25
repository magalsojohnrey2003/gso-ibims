<section>
    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <!-- Current Password -->
        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" class="input-label" />
            <div class="relative">
                <x-text-input id="update_password_current_password"
                              name="current_password"
                              type="password"
                              class="input-field mt-1 block w-full pr-10"
                              autocomplete="current-password" />
                <button type="button"
                        class="absolute inset-y-0 right-0 px-3 flex items-center muted"
                        onmousedown="togglePassword('update_password_current_password', this, true)"
                        onmouseup="togglePassword('update_password_current_password', this, false)"
                        onmouseleave="togglePassword('update_password_current_password', this, false)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <!-- New Password -->
        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" class="input-label" />
            <div class="relative">
                <x-text-input id="update_password_password"
                              name="password"
                              type="password"
                              class="input-field mt-1 block w-full pr-10"
                              autocomplete="new-password" />
                <button type="button"
                        class="absolute inset-y-0 right-0 px-3 flex items-center muted"
                        onmousedown="togglePassword('update_password_password', this, true)"
                        onmouseup="togglePassword('update_password_password', this, false)"
                        onmouseleave="togglePassword('update_password_password', this, false)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="input-label" />
            <div class="relative">
                <x-text-input id="update_password_password_confirmation"
                              name="password_confirmation"
                              type="password"
                              class="input-field mt-1 block w-full pr-10"
                              autocomplete="new-password" />
                <button type="button"
                        class="absolute inset-y-0 right-0 px-3 flex items-center muted"
                        onmousedown="togglePassword('update_password_password_confirmation', this, true)"
                        onmouseup="togglePassword('update_password_password_confirmation', this, false)"
                        onmouseleave="togglePassword('update_password_password_confirmation', this, false)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Save Button -->
        <div class="flex items-center justify-center pt-2">
            <x-primary-button class="px-8 py-2.5 text-base">{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</section>

<!-- Password Toggle Script -->
<script>
    function togglePassword(inputId, btn, show) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector("i");

        if (show) {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (@json(session('status') === 'password-updated')) {
            window.requestAnimationFrame(function () {
                window.showToast?.('Password updated successfully.', 'success');
            });
        }
    });
</script>
