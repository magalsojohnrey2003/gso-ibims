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
                                <option value="validated">Validated</option>
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
                                <th class="px-6 py-3 text-center">Borrowed Date</th>
                                <th class="px-6 py-3 text-center">Return Date</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adminManpowerTableBody" class="text-center">
                            <x-table-loading-state colspan="7" />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <template id="admin-manpower-empty-state-template">
        <x-table-empty-state colspan="7" />
    </template>

    <template id="badge-status-pending"><x-status-badge type="pending" text="Pending" /></template>
    <template id="badge-status-validated"><x-status-badge type="info" text="Validated" /></template>
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

    <x-modal name="adminManpowerApproveModal" maxWidth="2xl" background="transparent">
        <div class="w-full max-h-[85vh] bg-emerald-600 dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-emerald-600 text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-user-check"></i>
                        <span>Validate Manpower Request</span>
                    </h3>
                    <p class="text-sm text-emerald-100 mt-1">Confirm assignment details before approving this manpower request.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition"
                    @click="$dispatch('close-modal','adminManpowerApproveModal')"
                >
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900 px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-clipboard-list text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Request Summary</h4>
                        </div>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Requester</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="user">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Role</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="role">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="status">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Qty Requested</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="quantity">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-calendar-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Schedule</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Borrow Date</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="borrow_date">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Return Date</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="return_date">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-map-marker-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Location</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Municipality</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="municipality">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Barangay</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="barangay">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Specific Area</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-approve-field="location">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-bullseye text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Purpose</h4>
                        </div>
                        <p class="mt-2 text-gray-600 dark:text-gray-300" data-approve-field="purpose">—</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-file-signature text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Letter</h4>
                        </div>
                        <div class="mt-3 text-gray-600 dark:text-gray-300" data-approve-field="letter">No letter uploaded.</div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:max-w-sm">
                    <label for="adminApprovedQuantity" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">Approved Quantity</label>
                    <input
                        type="number"
                        min="1"
                        id="adminApprovedQuantity"
                        class="mt-1 block w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                    />
                    <p class="text-xs text-gray-500 mt-1">Cannot exceed requested quantity.</p>
                </div>
                <div class="flex items-center gap-3 justify-end w-full md:w-auto">
                    <x-button variant="secondary" @click="$dispatch('close-modal','adminManpowerApproveModal')">Cancel</x-button>
                    <x-button id="confirmAdminApproval" variant="success">Confirm Validation</x-button>
                </div>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerViewModal" maxWidth="2xl" background="transparent">
        <div class="w-full max-h-[85vh] bg-[#4C1D95] dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        <span>Manpower Request Overview</span>
                    </h3>
                    <p class="text-sm text-purple-100 mt-1">Inspect requester information, schedule, staffing totals, and supporting documents.</p>
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

            <div class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900 px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-clipboard-list text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Request Summary</h4>
                        </div>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Requester</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="user">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Role</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="role">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="status">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Qty (Approved / Requested)</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="quantity">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-calendar-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Schedule</h4>
                        </div>
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
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-map-marker-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Location</h4>
                        </div>
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
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-bullseye text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Purpose</h4>
                        </div>
                        <p class="mt-2 text-gray-600 dark:text-gray-300" data-view-field="purpose">—</p>
                    </div>

                    <div id="adminManpowerRejectionCard" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2 hidden">
                        <div class="flex items-center gap-2 text-red-600">
                            <i class="fas fa-circle-xmark text-sm"></i>
                            <h4 class="text-sm font-semibold tracking-wide uppercase">Rejection Reason</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Subject</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-view-field="rejection_subject">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Explanation</dt>
                                <dd class="mt-1 text-gray-600 dark:text-gray-300 whitespace-pre-line" data-view-field="rejection_detail">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-file-signature text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Letter</h4>
                        </div>
                        <div class="mt-3 text-gray-600 dark:text-gray-300" data-view-field="letter">No letter uploaded.</div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end">
                <x-button variant="secondary" @click="$dispatch('close-modal','adminManpowerViewModal')">Close</x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerRejectSelectModal" maxWidth="md" background="transparent">
        <div class="w-full max-h-[80vh] bg-red-600 dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-red-600 text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-circle-xmark"></i>
                        <span>Select Rejection Reason</span>
                    </h3>
                    <p class="text-sm text-red-100 mt-1">Choose from saved reasons or create a new one.</p>
                </div>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition" @click="$dispatch('close-modal','adminManpowerRejectSelectModal')">
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-white dark:bg-gray-900 px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div id="manpowerRejectReasonOptions" class="space-y-3 max-h-64 overflow-y-auto pr-1"></div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="manpowerRejectReasonChoice" value="__other__" id="manpowerRejectReasonOtherOption" class="text-purple-600 focus:ring-purple-500">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">Other</div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Enter a custom rejection reason</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-gray-50 dark:bg-gray-800 border-t border-red-200/60 dark:border-red-700 px-6 py-4 flex items-center justify-between">
                <x-button id="manpowerRejectReasonSelectCancelBtn" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal','adminManpowerRejectSelectModal')">
                    Cancel
                </x-button>
                <x-button id="manpowerRejectReasonSelectConfirmBtn" variant="danger" class="px-4 py-2 text-sm" disabled>
                    Confirm Reject
                </x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerRejectCustomModal" maxWidth="md" background="transparent">
        <div class="w-full max-h-[80vh] bg-red-600 dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-red-600 text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-pen-to-square"></i>
                        <span>Custom Rejection Reason</span>
                    </h3>
                    <p class="text-sm text-red-100 mt-1">Provide a subject and detailed explanation for rejecting this request.</p>
                </div>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition" @click="$dispatch('close-modal','adminManpowerRejectCustomModal')">
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-white dark:bg-gray-900 px-6 py-5 space-y-4 text-sm text-gray-700 dark:text-gray-300">
                <div>
                    <label for="manpowerRejectSubjectInput" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                    <input type="text" id="manpowerRejectSubjectInput" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm" placeholder="e.g. Insufficient lead time">
                </div>
                <div>
                    <label for="manpowerRejectDetailInput" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Detailed Reason</label>
                    <textarea id="manpowerRejectDetailInput" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm" placeholder="Share the specific reason for rejecting this request."></textarea>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-gray-50 dark:bg-gray-800 border-t border-red-200/60 dark:border-red-700 px-6 py-4 flex items-center justify-between">
                <x-button id="manpowerRejectReasonCustomBackBtn" variant="secondary" class="px-4 py-2 text-sm">
                    Back
                </x-button>
                <x-button id="manpowerRejectReasonCustomConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                    Save &amp; Reject
                </x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerRejectViewModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex items-start justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Rejection Reason Details</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="$dispatch('close-modal','adminManpowerRejectViewModal')">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Subject</p>
                    <p class="text-sm font-semibold text-gray-900" id="manpowerRejectReasonViewSubject"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Detailed Reason</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line" id="manpowerRejectReasonViewDetail"></p>
                </div>
            </div>
            <div class="flex justify-end">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal','adminManpowerRejectViewModal')">Close</x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="adminManpowerRejectDeleteModal" maxWidth="sm">
        <div class="p-6 space-y-5">
            <div class="flex items-start gap-3">
                <i class="fas fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Remove Rejection Reason</h3>
                    <p class="text-sm text-gray-600 mt-1">Are you sure you want to remove this saved reason?</p>
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                <p class="text-sm font-semibold text-gray-800" id="manpowerRejectReasonDeleteName"></p>
                <p class="text-xs text-gray-500 mt-1" id="manpowerRejectReasonDeleteUsage"></p>
            </div>
            <div class="flex items-center justify-end gap-3">
                <x-button id="manpowerRejectReasonDeleteCancelBtn" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal','adminManpowerRejectDeleteModal')">Cancel</x-button>
                <x-button id="manpowerRejectReasonDeleteConfirmBtn" variant="danger" class="px-4 py-2 text-sm">Remove</x-button>
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
