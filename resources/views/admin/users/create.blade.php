{{-- resources/views/admin/users/create.blade.php --}}
<x-app-layout>
    <div class="px-6 lg:px-8">
        <x-title level="h2" size="2xl" weight="bold" icon="user-plus" variant="s" iconStyle="circle" iconBg="gov-accent" iconColor="white">
            Create User
        </x-title>
    </div>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8">
            <div class="card p-6">
                {{-- The create form is shown in a modal on the index page; keep this page as a fallback for non-JS users --}}
                @include('admin.users._form', ['action' => route('admin.users.store'), 'method' => 'POST'])
            </div>
        </div>
    </div>

    @vite('resources/js/app.js')
</x-app-layout>
