{{-- resources/views/admin/walk-in/index.blade.php --}}
<x-app-layout>
    <div class="py-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2" size="2xl" weight="bold" icon="clipboard-document-check" variant="s" iconStyle="plain" iconColor="title-purple" compact="true"> Walk-in Requests </x-title>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text" id="walkin-live-search" placeholder="Search Borrower/Office" class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-72 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
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
                            <th class="px-6 py-3 text-center">Borrower</th>
                            <th class="px-6 py-3 text-center">Borrow Date</th>
                            <th class="px-6 py-3 text-center">Return Date</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="walkinTableBody" class="text-center">
                        <x-table-loading-state colspan="6" data-walkin-loading-row />
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <template id="walkin-empty-state-template">
        <x-table-empty-state colspan="6" />
    </template>

    <template id="walkin-borrower-empty-template">
        <tr>
            <td colspan="3" class="px-6 py-10 text-center text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <i class="fas fa-users-slash text-3xl text-gray-300"></i>
                    <p class="font-semibold">No borrower accounts found</p>
                    <p class="text-sm text-gray-500">Adjust the search filters or create a new user from the Users module.</p>
                </div>
            </td>
        </tr>
    </template>

    <template id="walkin-borrower-loading-template">
        <tr>
            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading borrower accounts...
            </td>
        </tr>
    </template>

    <template id="walkin-borrower-error-template">
        <tr>
            <td colspan="3" class="px-6 py-8 text-center text-red-600">
                <i class="fas fa-triangle-exclamation mr-2"></i> Unable to load borrower accounts. Please try again.
            </td>
        </tr>
    </template>

    <x-modal name="walkinDetailsModal" maxWidth="2xl" background="transparent">
        <div class="w-full max-h-[85vh] bg-[#4C1D95] flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-id-card text-white"></i>
                        <span>Walk-in Details</span>
                    </h3>
                    <p class="text-sm text-purple-100 mt-1">Review requester info, schedule, and delivery progress.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition"
                    @click="$dispatch('close-modal', 'walkinDetailsModal')"
                >
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-100 px-6 py-5 space-y-5 text-sm text-gray-700">
                <div id="walkin-status-banner" class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4">
                    <i class="fas fa-truck text-purple-600 text-lg"></i>
                    <p id="walkin-status-text" class="font-semibold text-purple-800">Walk-in Request</p>
                </div>

                <div id="walkin-modal-content" class="grid gap-4 md:grid-cols-2">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-clipboard-list text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 tracking-wide uppercase">Request Summary</h4>
                        </div>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Request ID</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="formatted_request_id">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Borrower</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="borrower_name">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Delivery</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="delivery_progress">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Total Items</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="total_items">—</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs uppercase text-gray-500">Purpose</dt>
                                <dd class="mt-1 text-gray-700" data-field="purpose">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-calendar-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 tracking-wide uppercase">Schedule</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Borrow Date</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="borrowed_schedule">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Return Date</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="returned_schedule">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-map-marker-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 tracking-wide uppercase">Location</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Address</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="address">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Office/Agency</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="office_agency">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500">Contact</dt>
                                <dd class="mt-1 font-medium text-gray-900" data-field="contact_number">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-box-open text-xs"></i>
                            <div class="text-xs font-semibold text-gray-900 tracking-wide uppercase">Items Requested</div>
                        </div>
                        <ul class="mt-2 space-y-1 text-gray-700 list-disc list-inside ml-2" data-field="items">—</ul>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-people-carry text-xs"></i>
                            <div class="text-xs font-semibold text-gray-900 tracking-wide uppercase">Manpower</div>
                        </div>
                        <div class="mt-3 text-gray-900 font-medium" data-field="manpower_role">—</div>
                    </div>

                    <div id="walkin-report-reason-card" class="hidden md:col-span-2 bg-red-50 border border-red-200 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-red-600">
                            <i class="fas fa-circle-xmark text-sm"></i>
                            <h4 class="text-sm font-semibold tracking-wide uppercase">Reject Reason</h4>
                        </div>
                        <div class="mt-3 space-y-1">
                            <div class="text-sm font-semibold text-gray-900" data-field="delivery_report_reason">—</div>
                            <p class="text-xs text-red-600" data-field="delivery_reported_at"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white border-t border-gray-200 px-6 py-4 flex flex-wrap justify-end gap-3">
                <x-button
                    variant="secondary"
                    iconName="x-circle"
                    class="px-4 py-2 text-sm"
                    @click="$dispatch('close-modal', 'walkinDetailsModal')"
                >
                    Close
                </x-button>
            </div>
        </div>
    </x-modal>

    {{-- Deliver Confirmation Modal --}}
    <x-modal name="walkinDeliverConfirmModal" maxWidth="md">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-truck text-purple-600 text-2xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Deliver Walk-in Request</h3>
            </div>

            <div class="mt-4 space-y-3">
                <p class="text-sm text-gray-600">
                    Are you sure you want to mark this walk-in request as delivered?
                </p>
                
                <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-user text-gray-400 mt-0.5"></i>
                        <div>
                            <span class="text-gray-500">Borrower:</span>
                            <span id="confirmBorrowerName" class="font-medium text-gray-900 ml-1">—</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-building text-gray-400 mt-0.5"></i>
                        <div>
                            <span class="text-gray-500">Office:</span>
                            <span id="confirmOfficeAgency" class="font-medium text-gray-900 ml-1">—</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-box-open text-gray-400 mt-0.5"></i>
                        <div class="w-full">
                            <span class="text-gray-500">Items:</span>
                            <ul id="confirmItemsList" class="list-disc list-inside text-gray-700 mt-1 ml-1">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-5">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'walkinDeliverConfirmModal')">
                    Cancel
                </x-button>
                <x-button id="walkinDeliverConfirmBtn" variant="primary" class="px-4 py-2 text-sm">
                    <i class="fas fa-check-circle mr-1"></i> Confirm Delivery
                </x-button>
            </div>
        </div>
    </x-modal>

    {{-- Confirm Delivered Modal --}}
    <x-modal name="walkinConfirmDeliveredModal" maxWidth="md">
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-100 text-purple-700"><i class="fas fa-box"></i></span>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Mark as Delivered</h3>
                    <p class="text-sm text-gray-600" id="confirmDeliveredSubtitle">Confirm items were handed over.</p>
                </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 space-y-2">
                <div><span class="text-gray-500">Request:</span> <span class="font-semibold" id="confirmDeliveredRequestId">—</span></div>
                <div><span class="text-gray-500">Borrower:</span> <span class="font-semibold" id="confirmDeliveredBorrower">—</span></div>
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'walkinConfirmDeliveredModal')">Cancel</x-button>
                <x-button id="walkinConfirmDeliveredBtn" class="px-4 py-2 text-sm">Confirm Delivered</x-button>
            </div>
        </div>
    </x-modal>

    {{-- Borrower Selection Modal --}}
    <x-modal name="walkinSelectBorrowerModal" maxWidth="xl">
        <div class="p-6 space-y-6">
            <div class="flex flex-col gap-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-3">
                            <i class="fas fa-users text-purple-600"></i>
                            <span>Select Borrower Account</span>
                        </h3>
                        <p class="text-sm text-gray-500">Search existing borrower accounts to pre-fill the walk-in request form.</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="$dispatch('close-modal', 'walkinSelectBorrowerModal')">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="relative w-full md:w-80">
                        <input type="text" id="walkin-borrower-search" placeholder="Search name, email, or phone" class="w-full rounded-lg border border-gray-300 pl-11 pr-3 py-2.5 text-sm focus:ring-purple-500 focus:border-purple-500" />
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="text-xs text-gray-500 flex items-center gap-2">
                        <i class="fas fa-circle-info text-purple-500"></i>
                        <span>Selecting an account will open the create form with borrower details pre-filled.</span>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-700">
                        <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-center">ID#</th>
                                <th class="px-6 py-3 text-center">Borrower</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="walkinBorrowerTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'walkinSelectBorrowerModal')">Close</x-button>
            </div>
        </div>
    </x-modal>

    {{-- Borrower Profile Modal --}}
    <x-modal name="walkinUserProfileModal" maxWidth="md">
        <div class="flex flex-col max-h-[85vh]">
            <div class="flex items-start justify-between bg-[#4C1D95] px-6 py-4 text-white">
                <div>
                    <h3 class="text-xl font-semibold leading-tight flex items-center gap-2">
                        <i class="fas fa-user-circle text-white"></i>
                        <span>Borrower Details</span>
                    </h3>
                    <p class="text-sm text-purple-100">Review this account before assigning it to a walk-in request.</p>
                </div>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition" @click="$dispatch('close-modal', 'walkinUserProfileModal')">
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="px-6 py-5 space-y-4 overflow-y-auto text-sm text-gray-700">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">ID#</dt>
                        <dd class="mt-1 font-semibold text-gray-900" data-user-profile="id">—</dd>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Joined</dt>
                        <dd class="mt-1 font-medium text-gray-800" data-user-profile="joined_at">—</dd>
                    </div>
                    <div class="sm:col-span-2 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Name</dt>
                        <dd class="mt-1 font-semibold text-gray-900" data-user-profile="name">—</dd>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</dt>
                        <dd class="mt-1 text-gray-800" data-user-profile="email">—</dd>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phone</dt>
                        <dd class="mt-1 text-gray-800" data-user-profile="phone">—</dd>
                    </div>
                    <div class="sm:col-span-2 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Address</dt>
                        <dd class="mt-1 text-gray-800" data-user-profile="address">—</dd>
                    </div>
                    <div class="sm:col-span-2 bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Standing</dt>
                        <dd class="mt-1" data-user-profile="standing">—</dd>
                    </div>
                </dl>
            </div>

            <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4 border-t border-gray-200">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'walkinUserProfileModal')">Close</x-button>
            </div>
        </div>
    </x-modal>

    <!-- Floating Action Menu -->
    <div x-data="{ open: false }" class="fixed bottom-8 right-8 z-40">
        <button
            @click="open = !open"
            :aria-expanded="open"
            type="button"
            class="relative rounded-full w-14 h-14 flex items-center justify-center shadow-lg focus:outline-none transition-all duration-300 transform"
            :class="open ? 'bg-red-600 hover:bg-red-700 scale-105' : 'bg-purple-600 hover:bg-purple-700 hover:scale-110'">
            <span aria-hidden="true"
                class="absolute inset-0 rounded-full opacity-0 transition-opacity duration-300"
                :class="open ? 'opacity-20 bg-black/10' : ''"></span>
            <svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                stroke="currentColor"
                fill="none"
                class="w-7 h-7 text-white transform transition-transform duration-300"
                :class="open ? 'rotate-45' : 'rotate-0'">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
            <span class="sr-only">Open walk-in actions</span>
        </button>
        <div
            x-show="open"
            x-transition.origin.bottom.right
            class="absolute bottom-20 right-0 flex flex-col gap-3 items-end"
            @click.outside="open = false">
            <button
                data-walkin-open-borrower
                @click="open = false"
                type="button"
                class="group bg-white text-purple-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-purple-200 hover:border-purple-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[240px]">
                <div class="bg-purple-100 p-2 rounded-lg group-hover:bg-purple-200 transition-colors">
                    <i class="fas fa-users"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-sm">Use Borrower Account</div>
                    <div class="text-xs text-purple-500">Search and pre-fill details</div>
                </div>
            </button>
            <button
                data-walkin-start-blank
                @click="open = false"
                type="button"
                class="group bg-white text-emerald-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-emerald-200 hover:border-emerald-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[240px]">
                <div class="bg-emerald-100 p-2 rounded-lg group-hover:bg-emerald-200 transition-colors">
                    <i class="fas fa-file-circle-plus"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-sm">Start Blank Request</div>
                    <div class="text-xs text-emerald-500">Open the walk-in form</div>
                </div>
            </button>
        </div>
    </div>

    <script>
        window.WALKIN_LIST_ROUTE = "{{ route('admin.walkin.list') }}";
        window.WALKIN_PRINT_ROUTE_TEMPLATE = "{{ route('admin.walkin.print', ['walkInRequest' => '__ID__']) }}";
        window.WALKIN_DELIVER_ROUTE_TEMPLATE = "{{ route('admin.walkin.deliver', ['id' => '__ID__']) }}";
        window.WALKIN_CONFIRM_DELIVERY_ROUTE_TEMPLATE = "{{ route('admin.walkin.confirm-delivery', ['id' => '__ID__']) }}";
        window.WALKIN_BORROWERS_ROUTE = "{{ route('admin.walkin.borrowers') }}";
        window.WALKIN_CREATE_ROUTE = "{{ route('admin.walkin.create') }}";
        window.WALKIN_BORROWER_SHOW_TEMPLATE = "{{ route('admin.users.show', ['user' => '__ID__']) }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

</x-app-layout>
