{{-- resources/views/admin/users/edit.blade.php --}}
<x-app-layout>
    <div class="px-6 lg:px-8">
        <x-title level="h2" size="2xl" weight="bold" icon="pencil-square" variant="s" iconStyle="circle" iconBg="gov-accent" iconColor="white">
            Edit User
        </x-title>
    </div>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8">
            <div class="card p-6">
                {{-- Edit form rendered via partial so it can be used in the modal on the index page. --}}
                @include('admin.users._form', [
                    'user' => $user,
                    'action' => route('admin.users.update', $user),
                    'method' => 'PATCH',
                ])
            </div>
        </div>
    </div>

    @vite('resources/js/app.js')
</x-app-layout>
