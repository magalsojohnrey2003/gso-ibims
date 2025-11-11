{{-- resources/views/admin/users/_row.blade.php --}}
<tr data-user-id="{{ $user->id }}">
    <td class="px-6 py-4 whitespace-nowrap">{{ $user->full_name ?? ($user->first_name . ' ' . $user->last_name) }}</td>
    <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
    <td class="px-6 py-4 whitespace-nowrap">{{ $user->created_at->format('Y-m-d') }}</td>
    <td class="px-6 py-4 whitespace-nowrap">
        @if($user->last_login_at)
            <span class="inline-flex items-center gap-1.5 text-sm" data-last-active="{{ $user->last_login_at->timestamp }}" title="Last login: {{ $user->last_login_at->format('M d, Y h:i A') }}">
                <i class="fas fa-circle text-xs {{ $user->last_login_at->isToday() ? 'text-green-500' : ($user->last_login_at->gt(now()->subDays(7)) ? 'text-yellow-500' : 'text-gray-400') }}"></i>
                <span class="last-active-text">{{ $user->last_login_at->diffForHumans() }}</span>
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-400">
                <i class="fas fa-circle text-xs text-gray-300"></i>
                <span>Never logged in</span>
            </span>
        @endif
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-center gap-3">
            <x-button
                variant="primary"
                size="sm"
                class="h-10 w-10 !px-0 !py-0 rounded-full shadow-lg [&>span:first-child]:mr-0 [&>span:last-child]:sr-only open-edit-modal"
                iconName="pencil-square"
                data-edit-url="{{ route('admin.users.edit', $user) }}">
                Edit
            </x-button>
            <form class="inline ajax-delete" action="{{ route('admin.users.destroy', $user) }}" method="POST">
                @csrf
                @method('DELETE')
                <x-button
                    variant="danger"
                    size="sm"
                    class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                    iconName="trash"
                    type="submit">
                    Delete
                </x-button>
            </form>
        </div>
    </td>
</tr>
