<section>
    <!-- Email Verification Form -->
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <!-- Profile Update Form -->
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

            @php
                $profilePhotoPath = Auth::user()->profile_photo;
                $currentPhotoUrl = $profilePhotoPath
                    ? asset('storage/' . $profilePhotoPath)
                    : asset('images/profile.jpg');
                $currentPhotoName = $profilePhotoPath ? basename($profilePhotoPath) : null;
            @endphp

            <div class="grid gap-8 lg:grid-cols-[280px,1fr]">
                <!-- Profile Photo Card -->
                <div class="border border-dashed border-purple-200 dark:border-purple-400/40 rounded-2xl bg-purple-50/60 dark:bg-purple-900/20 px-6 py-5 flex flex-col items-center gap-5 text-center">
                    <div class="space-y-2 w-full">
                        <x-input-label for="profile_photo" :value="__('Profile Picture')" class="text-sm font-semibold uppercase tracking-wide text-purple-700 dark:text-purple-200" />

                        <div class="mx-auto h-28 w-28 rounded-full border-4 border-white shadow-md overflow-hidden bg-white">
                            <img src="{{ $currentPhotoUrl }}"
                                 data-profile-photo-preview
                                 data-photo-default="{{ asset('images/profile.jpg') }}"
                                 data-photo-current="{{ $currentPhotoUrl }}"
                                 alt="Profile photo preview"
                                 class="h-full w-full object-cover">
                        </div>
                    </div>

                    <div class="w-full space-y-3">
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="sr-only" data-profile-photo-input>

                        <button type="button"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 text-white text-sm font-semibold px-4 py-2.5 shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 focus:ring-offset-purple-100 transition"
                                data-profile-photo-trigger>
                            <i class="fa-solid fa-cloud-arrow-up text-base"></i>
                            <span>{{ __('Upload Photo') }}</span>
                        </button>

                        <p class="text-xs text-purple-700 dark:text-purple-200" data-profile-photo-filename data-default-label="{{ $currentPhotoName ? $currentPhotoName : __('PNG or JPG up to 2MB.') }}">
                            {{ $currentPhotoName ? $currentPhotoName : __('PNG or JPG up to 2MB.') }}
                        </p>
                    </div>

                    <x-input-error class="mt-1" :messages="$errors->get('profile_photo')" />
                </div>

                <div class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-3" data-profile-name-row>
                        <div class="space-y-2" data-field-wrapper>
                            <x-input-label for="first_name" :value="__('First Name')" class="input-label" />
                            <x-text-input id="first_name" name="first_name" type="text"
                                          :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                            <x-input-error class="mt-1" :messages="$errors->get('first_name')" />
                        </div>

                        <div class="space-y-2" data-field-wrapper>
                            <x-input-label for="middle_name" :value="__('Middle Name (optional)')" class="input-label" />
                            <x-text-input id="middle_name" name="middle_name" type="text"
                                          :value="old('middle_name', $user->middle_name)" autocomplete="additional-name" />
                            <x-input-error class="mt-1" :messages="$errors->get('middle_name')" />
                        </div>

                        <div class="space-y-2" data-field-wrapper>
                            <x-input-label for="last_name" :value="__('Last Name')" class="input-label" />
                            <x-text-input id="last_name" name="last_name" type="text"
                                          :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                            <x-input-error class="mt-1" :messages="$errors->get('last_name')" />
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2" data-profile-contact-row>
                        <div class="space-y-2" data-field-wrapper>
                            <x-input-label for="phone" :value="__('Phone')" class="input-label" />
                            <x-text-input id="phone" name="phone" type="text" inputmode="numeric"
                                          :value="old('phone', $user->phone)" placeholder="09XXXXXXXXX" data-profile-phone />
                            <p class="text-xs text-red-500 hidden" data-profile-phone-error>{{ __('Phone number must be 11 digits.') }}</p>
                            <x-input-error class="mt-1" :messages="$errors->get('phone')" />
                        </div>

                        <div class="space-y-2" data-field-wrapper>
                            <x-input-label for="email" :value="__('Email')" class="input-label" />
                            <x-text-input id="email" name="email" type="email"
                                          :value="old('email', $user->email)" required autocomplete="username" data-profile-email />
                            <p class="text-xs text-red-500 hidden" data-profile-email-error>{{ __('Enter a valid email address.') }}</p>
                            <x-input-error class="mt-1" :messages="$errors->get('email')" />
                        </div>
                    </div>

                    <div class="space-y-2" data-field-wrapper>
                        <x-input-label for="address" :value="__('Address')" class="input-label" />
                        <textarea id="address" name="address" rows="3" maxlength="150" class="gov-input block w-full px-3 py-2 text-sm leading-tight rounded-xl transition duration-200 ease-out focus:outline-none focus:ring-0 resize-none">{{ old('address', $user->address) }}</textarea>
                        <x-input-error class="mt-1" :messages="$errors->get('address')" />
                    </div>

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700 space-y-2">
                            <p>{{ __('Your email address is unverified.') }}</p>
                            <button form="send-verification"
                                    class="inline-flex items-center gap-2 text-amber-800 font-medium hover:underline focus:outline-none">
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                <span>{{ __('Click here to re-send the verification email.') }}</span>
                            </button>

                            @if (session('status') === 'verification-link-sent')
                                <p class="font-semibold">{{ __('A new verification link has been sent to your email address.') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        <!-- Save Button -->
            <div class="flex items-center justify-center pt-2">
                <x-primary-button class="px-8 py-2.5 text-base">{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (@json(session('status') === 'profile-updated')) {
        window.requestAnimationFrame(function () {
            window.showToast?.('Profile information saved successfully.', 'success');
        });
    }

    const photoInput = document.querySelector('[data-profile-photo-input]');
    const photoTrigger = document.querySelector('[data-profile-photo-trigger]');
    const photoPreview = document.querySelector('[data-profile-photo-preview]');
    const photoFilename = document.querySelector('[data-profile-photo-filename]');
    const photoDefaultLabel = photoFilename ? photoFilename.getAttribute('data-default-label') : '';

    if (photoTrigger && photoInput) {
        photoTrigger.addEventListener('click', function () {
            photoInput.click();
        });
    }

    if (photoInput && photoPreview) {
        const defaultSrc = photoPreview.getAttribute('data-photo-default') || '';
        const currentSrc = photoPreview.getAttribute('data-photo-current') || defaultSrc;

        photoInput.addEventListener('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                photoPreview.src = currentSrc;
                if (photoFilename) {
                    photoFilename.textContent = photoDefaultLabel || 'PNG or JPG up to 2MB.';
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                if (event.target && typeof event.target.result === 'string') {
                    photoPreview.src = event.target.result;
                }
            };
            reader.readAsDataURL(file);

            if (photoFilename) {
                photoFilename.textContent = file.name;
            }
        });
    }

    const phoneInput = document.querySelector('[data-profile-phone]');
    const phoneError = document.querySelector('[data-profile-phone-error]');
    const emailInput = document.querySelector('[data-profile-email]');
    const emailError = document.querySelector('[data-profile-email-error]');

    const setFieldState = (input, errorEl, { state, message }) => {
        if (!input) return;
        const wrapper = input.closest('[data-field-wrapper]');
        const errorMessage = message || '';

        const removeStates = () => {
            input.classList.remove('ring-2', 'ring-red-500', 'ring-emerald-500', 'focus:ring-red-500', 'focus:ring-emerald-500');
            input.classList.add('focus:ring-0');
            if (wrapper) {
                wrapper.classList.remove('has-error');
            }
            if (errorEl) {
                errorEl.classList.add('hidden');
            }
            input.removeAttribute('aria-invalid');
        };

        removeStates();

        if (state === 'error') {
            input.classList.remove('focus:ring-0');
            input.classList.add('ring-2', 'ring-red-500', 'focus:ring-red-500');
            if (wrapper) {
                wrapper.classList.add('has-error');
            }
            if (errorEl) {
                errorEl.textContent = errorMessage;
                errorEl.classList.remove('hidden');
            }
            input.setAttribute('aria-invalid', 'true');
        } else if (state === 'success') {
            input.classList.remove('focus:ring-0');
            input.classList.add('ring-2', 'ring-emerald-500', 'focus:ring-emerald-500');
        }
    };

    const emailPattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,})$/;

    const hasServerError = (input) => {
        if (!input) return false;
        const wrapper = input.closest('[data-field-wrapper]');
        if (!wrapper) return false;
        return !!wrapper.querySelector('.text-sm.text-red-600, .text-sm.mt-1.text-red-600');
    };

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            const digits = (this.value || '').replace(/\D/g, '').slice(0, 11);
            this.value = digits;
            if (!digits) {
                setFieldState(this, phoneError, { state: null });
            } else if (digits.length === 11) {
                setFieldState(this, phoneError, { state: 'success' });
            } else {
                setFieldState(this, phoneError, { state: 'error', message: 'Phone number must be 11 digits.' });
            }
        });

        phoneInput.addEventListener('blur', function () {
            const digits = (this.value || '').replace(/\D/g, '');
            if (!digits) {
                setFieldState(this, phoneError, { state: null });
            } else if (digits.length !== 11) {
                setFieldState(this, phoneError, { state: 'error', message: 'Phone number must be 11 digits.' });
            } else {
                setFieldState(this, phoneError, { state: 'success' });
            }
        });

        const initialDigits = (phoneInput.value || '').replace(/\D/g, '');
        if (initialDigits) {
            phoneInput.value = initialDigits.slice(0, 11);
            if (!hasServerError(phoneInput)) {
                setFieldState(phoneInput, phoneError, {
                    state: initialDigits.length === 11 ? 'success' : 'error',
                    message: 'Phone number must be 11 digits.'
                });
            }
        }
    }

    if (emailInput) {
        const validateEmail = () => {
            const value = (emailInput.value || '').trim();
            if (!value) {
                setFieldState(emailInput, emailError, { state: 'error', message: 'Email is required.' });
            } else if (!emailPattern.test(value)) {
                setFieldState(emailInput, emailError, { state: 'error', message: 'Enter a valid email address.' });
            } else {
                setFieldState(emailInput, emailError, { state: 'success' });
            }
        };

        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', function () {
            if (!this.value) {
                setFieldState(emailInput, emailError, { state: null });
            } else if (emailPattern.test(this.value.trim())) {
                setFieldState(emailInput, emailError, { state: 'success' });
            }
        });

        if (emailInput.value && !hasServerError(emailInput)) {
            validateEmail();
        }
    }
});
</script>
