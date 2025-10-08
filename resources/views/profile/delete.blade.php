<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-900 theme-original:bg-purple-50
                rounded-xl shadow-lg p-6 transition-colors duration-300">

        <h2 class="text-lg font-medium text-red-600 dark:text-red-500">
            {{ __('Delete Account') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.
                 Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>

        <br>
        @include('profile.partials.delete-user-form')
    </div>
</x-app-layout>
