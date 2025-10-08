{{-- resources/views/auth/reset-password.blade.php --}}
<x-guest-layout>
    <div class="flex flex-col items-center text-center">
        <img src="{{ asset('images/logo2.png') }}" alt="Logo" class="w-20 h-20 rounded-md mb-4">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Reset Password</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Set a new strong password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        {{-- token passed from controller --}}
        <input type="hidden" name="token" value="{{ $request->route('token') ?? old('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full"
                          type="email" name="email" :value="old('email', $request->email ?? '')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                          type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                          type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-purple-600 hover:underline">Back to login</a>

            <x-primary-button class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700">
                <i class="fa-solid fa-key"></i>
                <span>{{ __('Reset Password') }}</span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
