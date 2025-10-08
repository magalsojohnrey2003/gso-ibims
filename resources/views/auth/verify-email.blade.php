{{-- resources/views/auth/verify-email.blade.php --}}
<x-guest-layout>
    <div class="flex flex-col items-center text-center">
        <img src="{{ asset('images/logo2.png') }}" alt="Logo" class="w-20 h-20 rounded-md mb-4">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Verify Your Email</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Before getting started, please verify your email address by clicking the link we emailed you.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-md bg-green-50 dark:bg-green-900/30 p-3 text-sm text-green-800 dark:text-green-200">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700">
                <i class="fa-solid fa-envelope-circle-check"></i>
                <span>{{ __('Resend Verification Email') }}</span>
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center mt-2 underline text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
