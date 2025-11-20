<x-app-layout>
    @php $noMainScroll = false; @endphp

    <div class="py-2">
        <div class="px-2">
            <div id="adminManpowerAlert" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2" size="2xl" weight="bold" icon="users" iconStyle="plain" iconColor="title-purple" compact="true"> Manpower Requests </x-title>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text" id="admin-manpower-search" placeholder="Search name, role, purpose"
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <div class="flex-shrink-0 relative">
                            <select id="admin-manpower-status" class="border border-gray-300 rounded-lg pl-4 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400 appearance-none bg-white">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <x-button id="openManageRoles" variant="secondary" iconName="cog" class="px-4 py-2 text-sm">Manage Role</x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pb-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
                <div class="table-container-no-scroll">
                    <table class="w-full text-sm text-center text-gray-600 gov-table">
                        <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                            <tr>
                                <th class="px-6 py-3 text-center">User</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3 text-center">Qty (Requested)</th>
                                <th class="px-6 py-3 text-center">Schedule</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adminManpowerTableBody" class="text-center">
                            <x-table-loading-state colspan="6" />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <template id="admin-manpower-empty-state-template">
        <x-table-empty-state colspan="6" />
    </template>

    <template id="badge-status-pending"><x-status-badge type="pending" text="Pending" /></template>
    <template id="badge-status-approved"><x-status-badge type="accepted" text="Approved" /></template>
    <template id="badge-status-rejected"><x-status-badge type="rejected" text="Rejected" /></template>

    <x-modal name="adminManageRolesModal" maxWidth="2xl">
        <div class="w-full bg-white dark:bg-gray-900 shadow-lg overflow-hidden flex flex-col max-h-[85vh]">
            <div class="bg-purple-600 text-white px-6 py-5 sticky top-0 z-20 relative">
                <button
                    type="button"
                    @click="$dispatch('close-modal','adminManageRolesModal')"
                    class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors p-2 hover:bg-white/10 rounded-lg"
                    aria-label="Close manage roles modal"
                >
                    <i class="fas fa-times"></i>
                </button>
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-users-cog"></i>
                    Manage Roles
                </h3>
                <p class="text-purple-100 mt-2 text-sm leading-relaxed">Add or remove manpower role types for future requests.</p>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-1 space-y-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="adminRoleName">Role Type</label>
                        <input type="text" id="adminRoleName" class="w-full rounded-lg border border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm" placeholder="e.g. Driver, Usher" />
                        <x-button id="adminSaveRole" variant="primary" class="w-full">Save</x-button>
                    </div>
                    <div class="md:col-span-2">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm table-wrapper">
                            <div class="table-container">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left text-gray-700 dark:text-gray-200">
                                    <thead class="bg-purple-600 dark:bg-purple-700 text-xs uppercase tracking-wide text-white">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Role Type</th>
                                            <th class="px-4 py-3 text-center w-24 font-semibold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminRolesTableBody" class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                                        <x-table-loading-state colspan="2" />
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerApproveModal" maxWidth="2xl">
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Validate Manpower Request</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','adminManpowerApproveModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                    <div class="text-gray-500 uppercase text-xs">Requester</div>
                    <div class="font-semibold text-gray-900" data-approve-field="user">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Role</div>
                    <div class="font-semibold text-gray-900" data-approve-field="role">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Qty Requested</div>
                    <div class="font-semibold text-gray-900" data-approve-field="quantity">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Schedule</div>
                    <div class="font-semibold text-gray-900" data-approve-field="schedule">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Municipality</div>
                    <div class="font-semibold text-gray-900" data-approve-field="municipality">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Barangay</div>
                    <div class="font-semibold text-gray-900" data-approve-field="barangay">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Purpose</div>
                    <div class="font-medium text-gray-900" data-approve-field="purpose">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Specific Area</div>
                    <div class="font-medium text-gray-900" data-approve-field="location">—</div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Approved Quantity</label>
                <input type="number" min="1" id="adminApprovedQuantity" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm" />
                <p class="text-xs text-gray-500 mt-1">Cannot exceed requested quantity.</p>
            </div>
            <div class="border-t pt-4">
                <div class="text-sm text-gray-500 uppercase mb-2">Letter</div>
                <div data-approve-field="letter" class="text-sm text-gray-700">No letter uploaded.</div>
            </div>
            <div class="flex justify-end gap-3">
                <x-button variant="secondary" @click="$dispatch('close-modal','adminManpowerApproveModal')">Cancel</x-button>
                <x-button id="confirmAdminApproval" variant="success">Confirm Approval</x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerViewModal" maxWidth="2xl">
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Request Details</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','adminManpowerViewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                    <div class="text-gray-500 uppercase text-xs">Requester</div>
                    <div class="font-semibold text-gray-900" data-view-field="user">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Status</div>
                    <div class="font-semibold text-gray-900" data-view-field="status">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Role</div>
                    <div class="font-semibold text-gray-900" data-view-field="role">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Qty (Approved / Requested)</div>
                    <div class="font-semibold text-gray-900" data-view-field="quantity">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Schedule</div>
                    <div class="font-semibold text-gray-900" data-view-field="schedule">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Municipality</div>
                    <div class="font-semibold text-gray-900" data-view-field="municipality">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Barangay</div>
                    <div class="font-semibold text-gray-900" data-view-field="barangay">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Purpose</div>
                    <div class="font-medium text-gray-900" data-view-field="purpose">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Specific Area</div>
                    <div class="font-medium text-gray-900" data-view-field="location">—</div>
                </div>
            </div>
            <div class="border-t pt-4">
                <div class="text-sm text-gray-500 uppercase mb-2">Letter</div>
                <div data-view-field="letter" class="text-sm text-gray-700">No letter uploaded.</div>
            </div>
        </div>
    </x-modal>

    <script>
        window.ADMIN_MANPOWER = {
            list: "{{ route('admin.manpower.requests.list') }}",
            status: function(id){ return "{{ url('/admin/manpower-requests') }}/"+id+"/status"; },
            roles: {
                list: "{{ route('manpower.roles.index') }}",
                store: "{{ route('admin.manpower.roles.store') }}",
                delete: function(id){ return "{{ url('/admin/manpower-roles') }}/"+id; },
            },
            csrf: "{{ csrf_token() }}"
        };
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
