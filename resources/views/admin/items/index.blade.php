<x-app-layout>
    <x-title level="h2"
                size="2xl"
                weight="bold"
                icon="archive-box"
                variant="s"
                iconStyle="plain"
                iconColor="gov-accent"> Items Management </x-title>

    <div class="py-6">
        <div class="sm:px-6 lg:px-8 space-y-10">
            @if(session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            @if(session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif

            @if($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <form method="GET" action="{{ route('items.index') }}" class="flex items-center gap-2">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search by name or category..."
                           class="border rounded-lg px-3 py-2 text-sm w-64 focus:ring focus:ring-blue-200" />

                    <x-button
                        variant="secondary"
                        iconName="magnifying-glass"
                        type="submit"
                        class="text-sm px-3 py-2">
                        Search
                    </x-button>
                </form>

                <x-button
                    class="text-sm px-3 py-2"
                    variant="primary"
                    iconName="plus"
                    x-data
                    x-on:click.prevent="$dispatch('open-modal', 'create-item')">
                    Add Items
                </x-button>
            </div>

            <div class="overflow-x-auto rounded-2xl shadow-lg">
                <table class="w-full text-sm text-left text-gray-600 shadow-sm border rounded-lg overflow-hidden">
                    <thead class="bg-purple-600 text-white text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-3">Photo</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Total Qty</th>
                            <th class="px-6 py-3">Available</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-white">
                        @forelse ($items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    @if($item->photo)
                                        <img src="{{ asset('storage/'.$item->photo) }}"
                                             class="h-12 w-12 object-cover rounded-lg shadow-sm">
                                    @else
                                        <x-status-badge type="gray" text="No photo" />
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-medium">{{ $item->name }}</td>
                                <td class="px-6 py-4">{{ ucfirst($item->category) }}</td>
                                <td class="px-6 py-4">{{ $item->total_qty }}</td>
                                <td class="px-6 py-4">
                                    <span class="{{ $item->available_qty > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                                        {{ $item->available_qty }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <x-button
                                        variant="primary"
                                        iconName="pencil-square"
                                        x-data
                                        x-on:click.prevent="$dispatch('open-modal', 'edit-item-{{ $item->id }}')">
                                        Edit
                                    </x-button>
                                    <x-button
                                        variant="danger"
                                        iconName="trash"
                                        x-data
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $item->id }}')">
                                        Delete
                                    </x-button>
                                </td>
                            </tr>

                            <x-modal name="edit-item-{{ $item->id }}" maxWidth="2xl">
                                <div class="p-6 space-y-4">
                                    <h2 class="text-lg font-bold text-gray-900">Edit Item</h2>
                                    @include('admin.items.edit', [
                                        'item' => $item,
                                        'categories' => $categories,
                                        'categoryPpeMap' => $categoryPpeMap,
                                    ])
                                </div>
                            </x-modal>

                            <x-modal name="confirm-delete-{{ $item->id }}">
                                <div class="p-6">
                                    <h2 class="text-lg font-bold text-red-600">
                                        Delete <strong>{{ $item->name }}</strong>?
                                    </h2>
                                    <p class="mt-2 text-sm text-gray-600">This action cannot be undone.</p>
                                    <div class="mt-6 flex justify-end space-x-3">
                                        <x-button
                                            variant="secondary"
                                            iconName="x-mark"
                                            x-on:click="$dispatch('close-modal', 'confirm-delete-{{ $item->id }}')">
                                            Cancel
                                        </x-button>
                                        <form method="POST" action="{{ route('items.destroy', $item->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-button
                                                variant="danger"
                                                iconName="trash"
                                                type="submit">Delete</x-button>
                                        </form>
                                    </div>
                                </div>
                            </x-modal>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <x-status-badge type="warning" text="No items found" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-modal name="create-item" maxWidth="2xl">
        <div class="p-6 space-y-4">
            <h2 class="text-lg font-bold text-gray-900">Add Items</h2>
            @include('admin.items.create', ['categories' => $categories, 'categoryPpeMap' => $categoryPpeMap])
        </div>
    </x-modal>
</x-app-layout>

<script>
    window.CATEGORY_PPE_MAP = @json($categoryPpeMap);
    document.addEventListener('DOMContentLoaded', () => {
        @if(session('success'))
            try { showToast(@json(session('success')), 'success'); } catch (e) {}
        @endif
        @if(session('error'))
            try { showToast(@json(session('error')), 'error'); } catch (e) {}
        @endif
    });
</script>
