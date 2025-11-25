<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-900 theme-original:bg-purple-50
                rounded-xl shadow-lg p-6 transition-colors duration-300">

        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i class="fa-solid fa-key text-amber-500" aria-hidden="true"></i>
            {{ __('Update Password') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>

        @include('profile.partials.update-password-form')
    </div>
</x-app-layout>
