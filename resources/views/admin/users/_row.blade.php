{{-- resources/views/admin/users/_row.blade.php --}}
<tr data-user-id="{{ $user->id }}">
    <td class="px-6 py-4 whitespace-nowrap">{{ $user->full_name ?? ($user->first_name . ' ' . $user->last_name) }}</td>
    <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
    <td class="px-6 py-4 whitespace-nowrap">{{ $user->created_at->format('Y-m-d') }}</td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <button data-edit-url="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3 open-edit-modal">Edit</button>
        <form class="inline ajax-delete" action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete user?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
        </form>
    </td>
</tr>
