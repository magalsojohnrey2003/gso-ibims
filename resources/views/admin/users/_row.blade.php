{{-- resources/views/admin/users/_row.blade.php --}}
<tr data-user-id="{{ $user->id }}">
    <td data-column="name" class="px-6 py-4 whitespace-nowrap truncate" title="{{ $user->full_name ?? ($user->first_name . ' ' . $user->last_name) }}">{{ $user->full_name ?? ($user->first_name . ' ' . $user->last_name) }}</td>
    <td data-column="email" class="px-6 py-4 whitespace-nowrap truncate" title="{{ $user->email }}">{{ $user->email }}</td>
    <td data-column="created" class="px-6 py-4 whitespace-nowrap truncate" title="{{ $user->creation_source ?? 'Borrower-Registered' }}">
        <span class="inline-flex items-center justify-center text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-700">
            {{ $user->creation_source ?? 'Borrower-Registered' }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap" title="{{ $user->created_at->format('M d, Y') }}">{{ $user->created_at->format('Y-m-d') }}</td>
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
        <div class="flex items-center justify-center gap-2">
            <x-button
                variant="secondary"
                size="sm"
                class="btn-action btn-utility h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only open-edit-modal"
                iconName="pencil-square"
                data-edit-url="{{ route('admin.users.edit', $user) }}">
                Edit
            </x-button>
            <form class="inline ajax-delete" action="{{ route('admin.users.destroy', $user) }}" method="POST">
                @csrf
                @method('DELETE')
                <x-button
                    variant="secondary"
                    size="sm"
                    class="btn-action btn-delete h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only"
                    iconName="trash"
                    type="submit">
                    Delete
                </x-button>
            </form>
        </div>
    </td>
</tr>
