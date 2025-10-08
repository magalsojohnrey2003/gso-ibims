<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-900 theme-original:bg-purple-50
                rounded-xl shadow-lg p-6 transition-colors duration-300">

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Update your account\'s profile information and email address.') }}
        </p>

        @include('profile.partials.update-profile-information-form')
    </div>
</x-app-layout>
