{{-- resources/views/admin/users/show.blade.php --}}
<x-app-layout>
    <div class="px-6 lg:px-8">
        <x-title level="h2" size="2xl" weight="bold" icon="user" variant="s" iconStyle="circle" iconBg="gov-accent" iconColor="white">
            User Details
        </x-title>
    </div>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8">
            <div class="card p-6">
                <h3 class="text-lg font-semibold mb-4">{{ $user->full_name }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p>{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Registered</p>
                        <p>{{ $user->created_at->toDayDateTimeString() }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('admin.users.index') }}" class="ml-3 btn">Back</a>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/app.js')
</x-app-layout>
