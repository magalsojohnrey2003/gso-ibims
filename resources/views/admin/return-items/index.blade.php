<x-app-layout>
    <div class="p-6 space-y-6">
        <x-title
            level="h2"
            size="2xl"
            weight="bold"
            icon="arrow-path-rounded-square"
            iconStyle="plain"
            iconColor="gov-accent">
            Return Items
        </x-title>

        <div id="alertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]" aria-live="assertive"></div>

        <div class="overflow-x-auto rounded-lg shadow">
            <table class="w-full text-sm text-center text-gray-600 shadow-sm border rounded-lg overflow-hidden">
                <thead class="bg-purple-600 text-white text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Borrow ID</th>
                        <th class="px-6 py-3">Borrower</th>
                        <th class="px-6 py-3">Delivery Status</th>
                        <th class="px-6 py-3">Condition</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="returnItemsTableBody" class="divide-y bg-white">
                    <tr>
                        <td colspan="5" class="py-4 text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="paginationContainer" class="mt-4 flex justify-center gap-2"></div>
    </div>

    <!-- Templates -->
    <template id="badge-status-pending">
        <x-status-badge type="pending" text="Pending" />
    </template>
    <template id="badge-status-approved">
        <x-status-badge type="accepted" text="Approved" />
    </template>
    <template id="badge-status-validated">
        <x-status-badge type="info" text="Validated" />
    </template>
    <template id="badge-status-rejected">
        <x-status-badge type="rejected" text="Rejected" />
    </template>
    <template id="badge-status-returned">
        <x-status-badge type="success" text="Returned" />
    </template>
    <template id="badge-status-dispatched">
        <x-status-badge type="info" text="Dispatched" />
    </template>
    <template id="badge-status-not_received">
        <x-status-badge type="warning" text="Not Received" />
    </template>

    <template id="badge-condition-good">
        <x-status-badge type="success" text="Good" />
    </template>
    <template id="badge-condition-missing">
        <x-status-badge type="danger" text="Missing" />
    </template>
    <template id="badge-condition-damage">
        <x-status-badge type="danger" text="Damage" />
    </template>
    <template id="badge-condition-minor_damage">
        <x-status-badge type="warning" text="Minor Damage" />
    </template>
    <template id="badge-condition-pending">
        <x-status-badge type="gray" text="Pending" />
    </template>

    <template id="alert-success-template">
        <x-alert type="success"><span data-alert-message></span></x-alert>
    </template>
    <template id="alert-error-template">
        <x-alert type="error"><span data-alert-message></span></x-alert>
    </template>

    <template id="action-manage-template">
        <x-button data-action="manage" variant="secondary" class="px-2 py-1 text-xs">
            <i class="fa-solid fa-pen-to-square mr-1"></i> Manage
        </x-button>
    </template>

    <!-- Manage Modal -->
    <x-modal name="manageReturnItemsModal" maxWidth="3xl">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fa-solid fa-screwdriver-wrench text-purple-600"></i>
                    <span>Manage Return</span>
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'manageReturnItemsModal')"
                    aria-label="Close dialog">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-hashtag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrow ID</div>
                        <div class="text-gray-600" id="manage-borrow-id">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-user text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrower</div>
                        <div class="text-gray-600" id="manage-borrower">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 md:col-span-2">
                    <i class="fas fa-map-marker-alt text-purple-600 mt-1"></i>
                    <div class="w-full">
                        <div class="font-medium text-gray-800">Address</div>
                        <x-text-input id="manage-address" type="text" class="mt-1 block w-full" readonly />
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-tag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Status</div>
                        <div id="manage-status-badge" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-truck text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Delivery Status</div>
                        <div id="manage-delivery-status" class="text-gray-700">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-day text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrow Date</div>
                        <div class="text-gray-600" id="manage-borrow-date">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return Date</div>
                        <div class="text-gray-600" id="manage-return-date">—</div>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg">
                <table class="w-full text-sm text-gray-700">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Property Number</th>
                            <th class="px-4 py-2 text-left">Condition</th>
                            <th class="px-4 py-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody id="manage-items-tbody" class="divide-y">
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'manageReturnItemsModal')">
                    Close
                </x-button>
            </div>
        </div>
    </x-modal>

    <script>
        window.RETURN_ITEMS_CONFIG = {
            list: "{{ route('admin.return-items.list') }}",
            base: "{{ url('/admin/return-items') }}",
            updateInstanceBase: "{{ url('/admin/return-items/instances') }}",
            csrf: "{{ csrf_token() }}"
        };
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
