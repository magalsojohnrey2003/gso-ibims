<x-app-layout>
    <div class="max-w-5xl mx-auto bg-white dark:bg-gray-900 theme-original:bg-purple-50
                rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 px-8 py-7 transition-colors duration-300">

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Update your account\'s profile information and email address.') }}
        </p>

        @include('profile.partials.update-profile-information-form')
    </div>
</x-app-layout>
