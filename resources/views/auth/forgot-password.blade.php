{{-- resources/views/auth/forgot-password.blade.php --}}
<x-guest-layout>
    <div class="flex flex-col items-center text-center">
        <img src="{{ asset('images/logo2.png') }}" alt="Logo" class="w-28 h-28 rounded-md mb-4">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100">GSO Item Borrowing & Inventory</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tagoloan Municipal Government — General Services Office</p>
    </div>

    <div class="mt-8 border-l-4 border-purple-500 pl-4">
        <h2 class="text-lg font-bold text-purple-600">FORGOT YOUR PASSWORD?</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            Enter your email and we'll send a secure link so you can pick a new password.
        </p>
    </div>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full"
                          type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-purple-600 hover:underline">Back to login</a>

            <x-primary-button class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700">
                <i class="fa-solid fa-envelope"></i>
                <span>{{ __('Email Password Reset Link') }}</span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
