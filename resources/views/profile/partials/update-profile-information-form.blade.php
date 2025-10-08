<section>
    <!-- Email Verification Form -->
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <!-- Profile Update Form -->
    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

       <!-- Profile Photo -->
       <div>
            <x-input-label for="profile_photo" :value="__('Profile Picture')" class="input-label" />

            <x-text-input id="profile_photo"
                          type="file"
                          name="profile_photo"
                          class="input-field mt-1 block w-full"
                          accept="image/*" />

            @if(Auth::user()->profile_photo)
                <div class="mt-2">
                    <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}"
                         alt="Profile Photo"
                         class="w-20 h-20 rounded-full object-cover">
                </div>
            @else
                <div class="mt-2">
                    <img src="{{ asset('images/profile.jpg') }}"
                         alt="Default Profile Photo"
                         class="w-20 h-20 rounded-full object-cover">
                </div>
            @endif

            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
       </div>

        <!-- First Name -->
        <div>
            <x-input-label for="first_name" :value="__('First Name')" class="input-label" />
            <x-text-input id="first_name" name="first_name" type="text" class="input-field mt-1 block w-full"
                          :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <!-- Middle Name -->
        <div class="mt-4">
            <x-input-label for="middle_name" :value="__('Middle Name (optional)')" class="input-label" />
            <x-text-input id="middle_name" name="middle_name" type="text" class="input-field mt-1 block w-full"
                          :value="old('middle_name', $user->middle_name)" autocomplete="additional-name" />
            <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
        </div>

        <!-- Last Name -->
        <div class="mt-4">
            <x-input-label for="last_name" :value="__('Last Name')" class="input-label" />
            <x-text-input id="last_name" name="last_name" type="text" class="input-field mt-1 block w-full"
                          :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('Phone')" class="input-label" />
            <x-text-input id="phone" name="phone" type="text" class="input-field mt-1 block w-full"
                          :value="old('phone', $user->phone)" placeholder="09987654321" />
            <p id="phone_error" class="text-xs mt-1 hidden">Invalid</p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <!-- Address -->
        <div>
            <x-input-label for="address" :value="__('Address')" class="input-label" />
            <textarea id="address" name="address" class="input-field mt-1 block w-full" rows="2" maxlength="150">{{ old('address', $user->address) }}</textarea>
            <p id="address_error" class="text-xs mt-1 hidden">Invalid</p>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="input-label" />
            <x-text-input id="email" name="email" type="email" class="input-field mt-1 block w-full"
                          :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 muted">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification"
                                class="underline text-sm rounded-md focus:outline-none">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm muted">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Save Button -->
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm muted"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
