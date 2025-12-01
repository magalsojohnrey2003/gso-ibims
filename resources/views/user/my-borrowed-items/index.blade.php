<x-app-layout>
    @php
        $noMainScroll = false; // Enable main content scrolling since we removed table scrollbar
    @endphp

    <!-- Title and Actions Section -->
    <div class="py-2">
        <div class="px-2">
            <!-- Alerts -->
            <div id="userAlertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>

            <!-- Title Row -->
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex-shrink-0 flex items-center">
                        <x-title
                            level="h2"
                            size="2xl"
                            weight="bold"
                            icon="clipboard-document-check"
                            variant="s"
                            iconStyle="plain"
                            iconColor="title-purple"
                            compact="true"
                        >
                            My Borrowed Items
                        </x-title>
                    </div>

                    <!-- Search Bar and Sort By -->
                    <div class="flex items-center gap-3">
                        <!-- Live Search Bar -->
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input
                                type="text"
                                id="my-borrowed-items-live-search"
                                placeholder="Search Request ID"
                                class="gov-input pl-12 pr-4 py-2.5 text-sm w-64 transition duration-200 focus:outline-none focus:ring-0"
                            />
                        </div>

                        <!-- Sort By Status -->
                        <div class="flex-shrink-0 relative">
                            <select
                                id="my-borrowed-items-status-filter"
                                class="gov-input pl-4 pr-4 py-2.5 text-sm transition duration-200 appearance-none focus:outline-none focus:ring-0"
                            >
                                <option value="">All Status</option>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="validated">Validated</option>
                                <option value="returned">Returned</option>
                                <option value="rejected">Rejected</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="pb-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
                <div class="table-container-no-scroll">
                    <table class="w-full text-sm text-center text-gray-600 gov-table">
                        <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                            <tr>
                                <th class="px-6 py-3 text-center">Request ID</th>
                                <th class="px-6 py-3 text-center">Borrow Date</th>
                                <th class="px-6 py-3 text-center">Return Date</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="myBorrowedItemsTableBody" class="text-center">
                            <x-table-loading-state colspan="5" />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <template id="my-borrowed-items-empty-state-template">
        <x-table-empty-state colspan="5" />
    </template>

    <!-- Borrow request details modal -->
    <x-modal name="borrowDetailsModal" maxWidth="2xl" background="transparent">
        <div
            id="borrowDetailsModalRoot"
            x-data="{ itemsOpen: false, rejectionOpen: false }"
            x-on:open-modal.window="if ($event.detail === 'borrowDetailsModal') { itemsOpen = false; rejectionOpen = false; }"
            class="w-full max-h-[85vh] bg-[#4C1D95] dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl"
        >
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3 rounded-t-2xl">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-box"></i>
                        <span>Request Item Details</span>
                    </h3>
                    <p class="text-sm text-purple-100 mt-1">Track schedule, status, and items included in this request.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition"
                    @click="$dispatch('close-modal', 'borrowDetailsModal')"
                >
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900 px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div id="mbi-status-banner" class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4">
                    <i class="fas fa-info-circle text-purple-600 text-lg"></i>
                    <p id="mbi-short-status" class="font-semibold text-purple-800">Borrow Request</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-clipboard-list text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Request Summary</h4>
                        </div>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Request ID</dt>
                                <dd id="mbi-summary-id" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</dt>
                                <dd id="mbi-summary-status" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Items</dt>
                                <dd id="mbi-summary-items" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Purpose</dt>
                                <dd id="mbi-summary-purpose" class="mt-1 text-gray-700 dark:text-gray-300">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-calendar-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Schedule</h4>
                        </div>
                        <div id="mbi-schedule-overdue-alert" class="hidden mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700 flex items-start gap-3">
                            <i class="fas fa-triangle-exclamation text-red-600"></i>
                            <div id="mbi-schedule-overdue-alert-text" class="text-sm font-semibold">This request is overdue.</div>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Borrow Date</dt>
                                <dd id="mbi-schedule-borrow" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Return Date</dt>
                                <dd id="mbi-schedule-return" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div id="mbi-schedule-time-row">
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Time of Usage</dt>
                                <dd id="mbi-schedule-time" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
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
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Municipality / City</dt>
                                <dd id="mbi-location-municipality" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Barangay</dt>
                                <dd id="mbi-location-barangay" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Specific Area</dt>
                                <dd id="mbi-location-area" class="mt-1 font-medium text-gray-900 dark:text-white">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-purple-700">
                                <i class="fas fa-boxes text-sm"></i>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Items</h4>
                            </div>
                            <button
                                type="button"
                                class="text-xs font-semibold uppercase tracking-wide text-purple-600 hover:text-purple-700"
                                @click="itemsOpen = !itemsOpen"
                            >
                                <span x-text="itemsOpen ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                        <p id="mbi-items-summary" class="mt-2 text-sm text-gray-500 dark:text-gray-400">0 items</p>
                        <div x-show="itemsOpen" x-cloak class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 space-y-6">
                            <div>
                                <p class="text-xs uppercase text-gray-500 dark:text-gray-400 mb-2">Requested Items</p>
                                <ul id="mbi-items" class="space-y-1 text-gray-600 dark:text-gray-300 list-disc list-inside"></ul>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-500 dark:text-gray-400 mb-2">Requested Manpower</p>
                                <ul id="mbi-manpower" class="space-y-1 text-gray-600 dark:text-gray-300 list-disc list-inside"></ul>
                            </div>
                        </div>
                    </div>

                    <div id="mbi-rejection-block" class="hidden md:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-200/70 dark:border-red-500/40 p-4">
                            <button
                                type="button"
                                class="w-full flex items-center justify-between text-left"
                                @click="rejectionOpen = !rejectionOpen"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="flex items-center gap-2 text-red-600">
                                        <i class="fas fa-circle-xmark text-sm"></i>
                                        <span class="text-sm font-semibold tracking-wide uppercase">Rejection Reason</span>
                                    </div>
                                    <div class="mt-0.5">
                                        <p id="mbi-rejection-summary" class="text-sm text-gray-600 dark:text-gray-300">Tap to view details</p>
                                    </div>
                                </div>
                                <i class="fas" :class="rejectionOpen ? 'fa-chevron-up text-red-500' : 'fa-chevron-down text-red-300'"></i>
                            </button>
                            <div x-show="rejectionOpen" x-cloak class="mt-4 border-t border-red-200 dark:border-red-600 pt-4 space-y-2 text-sm">
                                <p id="mbi-rejection-subject" class="font-semibold text-gray-900 dark:text-gray-100"></p>
                                <p id="mbi-rejection-reason" class="text-gray-700 dark:text-gray-300 whitespace-pre-line"></p>
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2 hidden" id="mbi-delivery-reason-block">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="font-semibold text-indigo-700 dark:text-indigo-300 mb-2 flex items-center gap-2">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Delivery Reason</span>
                                    </div>
                                    <div id="mbi-delivery-reason-content" class="text-sm text-gray-600 dark:text-gray-300"></div>
                                </div>
                                <button
                                    type="button"
                                    id="mbi-delivery-reason-toggle"
                                    class="hidden text-indigo-600 hover:text-indigo-800 text-xs font-medium flex items-center gap-1"
                                >
                                    <span id="mbi-delivery-reason-toggle-text">Show more</span>
                                    <i class="fas fa-chevron-down" id="mbi-delivery-reason-toggle-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex flex-wrap justify-end gap-2">
                {{-- Report/Confirm actions moved to table action column to reduce redundancy in modal --}}

                <x-button variant="secondary" iconName="x-circle" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'borrowDetailsModal')">
                    Close
                </x-button>
            </div>
        </div>
    </x-modal>

    <!-- Templates for action buttons -->
    <template id="btn-view-template">
        <x-button iconName="eye" variant="secondary" class="btn-action btn-view h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="view" title="View request details">View</x-button>
    </template>
    <template id="btn-print-template">
        <x-button iconName="printer" variant="secondary" class="btn-action btn-print h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="print" title="Open printable slip">Print</x-button>
    </template>
    <template id="btn-confirm-delivery-template">
        <x-button iconName="check-circle" variant="success" class="btn-action btn-confirm h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only hover:bg-emerald-600/10 focus:bg-emerald-600/10" data-action="confirm-delivery" title="Confirm items were received">
            <span class="sr-only">Confirm Received</span>
        </x-button>
    </template>

    <template id="btn-report-not-received-template">
        <x-button variant="danger" class="btn-action btn-report h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="report-not-received" title="Report not received">
            <x-slot name="icon">
                <i class="fas fa-triangle-exclamation text-[0.8rem]"></i>
            </x-slot>
            <span class="sr-only">Report Not Received</span>
        </x-button>
    </template>

    <x-modal name="confirmReportNotReceivedModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex items-center gap-3">
                <i class="fas fa-triangle-exclamation text-red-600 text-xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Report Not Received</h3>
            </div>
            <p class="text-sm text-gray-600">Are you sure you want to report that these items were not received?</p>
            <div>
                <label for="confirmReportNotReceivedReason" class="text-xs font-semibold text-gray-600 uppercase">Optional note to admin</label>
                <textarea id="confirmReportNotReceivedReason" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm" placeholder="Describe the issue (optional)"></textarea>
                <p class="mt-1 text-xs text-gray-500">Leaving this blank will still send the report.</p>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <x-button id="confirmReportNotReceivedCancelBtn" variant="secondary" class="px-4 py-2 text-sm">Cancel</x-button>
                <x-button id="confirmReportNotReceivedConfirmBtn" variant="danger" class="px-4 py-2 text-sm">Report</x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirmReceiveModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex items-center gap-3">
                <i class="fas fa-box-open text-indigo-600 text-xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Confirm Receipt</h3>
            </div>
            <p class="text-sm text-gray-600">
                You are about to confirm that all items for
                <span id="confirmReceiveRequestLabel" class="font-semibold text-gray-900">this request</span>
                have been received in good condition. Proceeding will notify the administrator.
            </p>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <x-button id="confirmReceiveCancelBtn" variant="secondary" class="px-4 py-2 text-sm">Cancel</x-button>
                <x-button id="confirmReceiveConfirmBtn" variant="success" class="px-4 py-2 text-sm">Confirm Receipt</x-button>
            </div>
        </div>
    </x-modal>

    <!-- Alert templates -->
    <template id="alert-success-template">
        <div><x-alert type="success"><span data-alert-message></span></x-alert></div>
    </template>
    <template id="alert-error-template">
        <div><x-alert type="error"><span data-alert-message></span></x-alert></div>
    </template>
    <template id="alert-info-template">
        <div><x-alert type="info"><span data-alert-message></span></x-alert></div>
    </template>
    <template id="alert-warning-template">
        <div><x-alert type="warning"><span data-alert-message></span></x-alert></div>
    </template>

    <!-- Server-rendered status badge templates (uses your x-status-badge component) -->
    <div id="statusBadgeTemplates" class="hidden" aria-hidden="true">
        <template data-status="approved"><x-status-badge type="accepted" text="Approved" /></template>
        <template data-status="pending"><x-status-badge type="pending" text="Pending" /></template>
        <template data-status="return_pending"><x-status-badge type="info" text="Return Pending" /></template>
        <template data-status="returned"><x-status-badge type="success" text="Returned" /></template>
        <template data-status="rejected"><x-status-badge type="rejected" text="Rejected" /></template>
        <template data-status="qr_verified"><x-status-badge type="accepted" text="Approved" /></template>
        <template data-status="default"><x-status-badge type="gray" text="—" /></template>
        <template data-status="validated"><x-status-badge type="info" text="Validated" /></template>
        <template data-status="dispatched"><x-status-badge type="info" text="Dispatched" /></template>
        <template data-status="delivered"><x-status-badge type="delivered" text="Delivered" /></template>
        <template data-status="not_received"><x-status-badge type="danger" text="Not Received" /></template>
    </div>

    <!-- Small bootstrap variables for the module -->
    <script>
        // IMPORTANT: these must match the route names in your web.php
        window.LIST_ROUTE = "{{ route('user.borrowed.items.list') }}";    // GET JSON list
        window.CSRF_TOKEN = "{{ csrf_token() }}";

        // Helper to pull badge HTML from the hidden templates
        window.renderStatusBadge = function(status) {
            const raw = (status || '').toLowerCase();
            const key = raw === 'qr_verified' ? 'approved' : raw;
            const tpl = document.querySelector(`#statusBadgeTemplates template[data-status="${key}"]`)
                      || document.querySelector('#statusBadgeTemplates template[data-status="default"]');
            return tpl ? tpl.innerHTML : `<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700"><i class="fas fa-question-circle text-xs"></i><span>${status || '—'}</span></span>`;
        };
    </script>

    <script>
    // Live search and filter functionality for My Borrowed Items
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('my-borrowed-items-live-search');
        const statusFilter = document.getElementById('my-borrowed-items-status-filter');
        const tableBody = document.getElementById('myBorrowedItemsTableBody');
        
        if (!searchInput || !statusFilter || !tableBody) return;
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const statusValue = statusFilter.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr[data-request-id]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const requestIdCell = row.querySelector('td:nth-child(1)');
                const statusCell = row.querySelector('td:nth-child(4)');
                
                if (!requestIdCell || !statusCell) return;
                
                const requestIdText = requestIdCell.textContent.toLowerCase();
                const statusText = statusCell.textContent.toLowerCase();
                
                // Check search match
                const searchMatches = requestIdText.includes(searchTerm);
                
                // Check status filter match
                let statusMatches = false;
                if (!statusValue) {
                    statusMatches = true;
                } else if (statusValue === 'overdue') {
                    // Determine overdue by checking return date cell text for the 'overdue' marker
                    const returnCell = row.querySelector('td:nth-child(3)');
                    const returnText = returnCell ? returnCell.textContent.toLowerCase() : '';
                    statusMatches = returnText.includes('overdue');
                } else {
                    statusMatches = statusText.includes(statusValue);
                }
                
                // Show/hide row
                if (searchMatches && statusMatches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Handle empty state
            const loadingRow = tableBody.querySelector('tr:not([data-request-id])');
            if (visibleCount === 0 && rows.length > 0 && !loadingRow) {
                let noResultsRow = document.getElementById('no-results-row-borrowed');
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row-borrowed';
                    noResultsRow.innerHTML = `
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-medium">No items found</p>
                                <p class="text-sm">Try adjusting your search or filter</p>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(noResultsRow);
                }
            } else {
                const noResultsRow = document.getElementById('no-results-row-borrowed');
                if (noResultsRow) {
                    noResultsRow.remove();
                }
            }
        }
        
        // Event listeners
        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
        
        // Dynamic placeholder change
        searchInput.addEventListener('focus', function() {
            this.placeholder = 'Type to Search';
        });
        
        searchInput.addEventListener('blur', function() {
            this.placeholder = 'Search Request ID';
        });
    });
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
