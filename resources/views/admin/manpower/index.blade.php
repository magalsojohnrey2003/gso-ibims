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
                            <input type="text" id="admin-manpower-search" placeholder="Search name, role, purpose, or request ID"
                                class="gov-input pl-12 pr-4 py-2.5 text-sm w-64 transition duration-200 focus:outline-none focus:ring-0" />
                        </div>
                        <div class="flex-shrink-0 relative">
                            <select id="admin-manpower-status" class="gov-input pl-4 pr-4 py-2.5 text-sm transition duration-200 appearance-none focus:outline-none focus:ring-0">
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
                                <th class="px-6 py-3 text-center">Request ID</th>
                                <th class="px-6 py-3 text-center">User</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3 text-center">Qty (Requested)</th>
                                <th class="px-6 py-3 text-center">Borrowed Date</th>
                                <th class="px-6 py-3 text-center">Return Date</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adminManpowerTableBody" class="text-center">
                            <x-table-loading-state colspan="8" />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <template id="admin-manpower-empty-state-template">
        <x-table-empty-state colspan="8" />
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
                        <input type="text" id="adminRoleName" class="gov-input w-full text-sm transition duration-200" placeholder="e.g. Driver, Usher" />
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
                    <div class="text-gray-500 uppercase text-xs">Borrowed Date</div>
                    <div class="font-semibold text-gray-900" data-approve-field="borrow_date">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Return Date</div>
                    <div class="font-semibold text-gray-900" data-approve-field="return_date">—</div>
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
        <div class="w-full max-h-[85vh] bg-gray-100 dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl">
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        <span>Request Details</span>
                    </h3>
                    <p class="text-sm text-purple-100 mt-1">Full breakdown of the manpower request.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition"
                    @click="$dispatch('close-modal','adminManpowerViewModal')"
                >
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Request Summary</h4>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Requester</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="user">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="status">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Role</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="role">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Qty (Approved / Requested)</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="quantity">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Schedule</h4>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Borrow Date</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="borrow_date">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Return Date</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="return_date">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Location</h4>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Municipality</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="municipality">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Barangay</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="barangay">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Specific Area</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="location">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Purpose</h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300" data-view-field="purpose">—</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Letter</h4>
                        <div class="mt-3 text-gray-600 dark:text-gray-300" data-view-field="letter">No letter uploaded.</div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end">
                <x-button variant="secondary" @click="$dispatch('close-modal','adminManpowerViewModal')">Close</x-button>
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
