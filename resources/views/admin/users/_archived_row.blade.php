{{-- resources/views/admin/users/_archived_row.blade.php --}}
@php
    $rawPhone = preg_replace('/\D+/', '', (string) ($user->phone ?? ''));
    if ($rawPhone === '') {
        $displayPhone = '—';
    } elseif (strlen($rawPhone) <= 4) {
        $displayPhone = $rawPhone;
    } elseif (strlen($rawPhone) <= 7) {
        $displayPhone = substr($rawPhone, 0, 4) . '-' . substr($rawPhone, 4);
    } else {
        $displayPhone = substr($rawPhone, 0, 4) . '-' . substr($rawPhone, 4, 3) . '-' . substr($rawPhone, 7);
    }
@endphp

<tr data-archived-user-id="{{ $user->id }}">
    <td class="px-6 py-4 whitespace-nowrap text-left font-semibold text-gray-800">{{ $user->full_name }}</td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $displayPhone }}</td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ optional($user->deleted_at)->timezone(config('app.timezone'))->format('M. d, Y g:i A') }}</td>
    <td class="px-6 py-4">
        <div class="flex items-center justify-center gap-3">
            <form class="inline ajax-restore" action="{{ route('admin.users.restore', $user->id) }}" method="POST">
                @csrf
                <x-button
                    variant="secondary"
                    size="sm"
                    class="btn-action btn-utility btn-restore h-10 w-28"
                    iconName="arrow-path"
                    type="submit">
                    Restore
                </x-button>
            </form>
            <form class="inline ajax-force-delete" action="{{ route('admin.users.force-destroy', $user->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <x-button
                    variant="danger"
                    size="sm"
                    class="btn-action btn-delete h-10 w-28"
                    iconName="trash"
                    type="submit">
                    Delete
                </x-button>
            </form>
        </div>
    </td>
</tr>
