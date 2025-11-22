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

    <!-- Modal (refreshed styling) -->
    <x-modal name="borrowDetailsModal" maxWidth="lg">
        <div
            id="borrowDetailsModalRoot"
            x-data="{ itemsOpen: false, rejectionOpen: false }"
            x-on:open-modal.window="if ($event.detail === 'borrowDetailsModal') { itemsOpen = false; rejectionOpen = false; }"
            class="w-full max-h-[85vh] bg-gray-100 dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl"
        >
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3 rounded-t-2xl">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fa-solid fa-box"></i>
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

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <div id="mbi-status-banner" class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm">
                    <i class="fas fa-info-circle text-purple-600 text-lg"></i>
                    <p id="mbi-short-status" class="font-semibold text-purple-800">Request ID #</p>
                </div>

                <div id="mbi-modal-content" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-start gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-700">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Borrow Date</div>
                            <div class="text-gray-600 dark:text-gray-300" id="mbi-borrow-date">—</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-start gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-700">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Return Date</div>
                            <div class="text-gray-600 dark:text-gray-300" id="mbi-return-date">—</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-start gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-700">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Delivery Location</div>
                            <div class="text-gray-600 dark:text-gray-300" id="mbi-location">—</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-start gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-700">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Time of Usage</div>
                            <div class="text-gray-600 dark:text-gray-300" id="mbi-usage-range">—</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-start gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-700">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Status</div>
                            <div id="mbi-status-badge" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">—</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:col-span-2">
                        <button
                            type="button"
                            class="w-full flex items-center justify-between text-left"
                            @click="itemsOpen = !itemsOpen"
                        >
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-700">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Items</div>
                                    <p id="mbi-items-summary" class="text-sm text-gray-500 dark:text-gray-400">0 items</p>
                                </div>
                            </div>
                            <i class="fas" :class="itemsOpen ? 'fa-chevron-up text-purple-600' : 'fa-chevron-down text-gray-400'"></i>
                        </button>
                        <div x-show="itemsOpen" x-cloak class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <ul id="mbi-items" class="space-y-1 text-gray-600 dark:text-gray-300 list-disc list-inside"></ul>
                        </div>
                    </div>

                    <div id="mbi-rejection-block" class="sm:col-span-2 hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-200/70 dark:border-red-500/40 p-4">
                            <button
                                type="button"
                                class="w-full flex items-center justify-between text-left"
                                @click="rejectionOpen = !rejectionOpen"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-full bg-red-100 text-red-600">
                                        <i class="fas fa-circle-xmark"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-red-700">Rejection Reason</div>
                                        <p id="mbi-rejection-summary" class="text-sm text-red-600/80">Tap to view details</p>
                                    </div>
                                </div>
                                <i class="fas" :class="rejectionOpen ? 'fa-chevron-up text-red-500' : 'fa-chevron-down text-red-300'"></i>
                            </button>
                            <div x-show="rejectionOpen" x-cloak class="mt-4 border-t border-red-200 dark:border-red-600 pt-4 space-y-2 text-sm">
                                <p id="mbi-rejection-subject" class="font-semibold text-red-700"></p>
                                <p id="mbi-rejection-reason" class="text-red-600 dark:text-red-300 whitespace-pre-line"></p>
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
                <x-button id="mbi-report-not-received-btn" variant="danger" class="px-4 py-2 text-sm hidden">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Report Not Received
                </x-button>

                <x-button id="mbi-confirm-received-btn" variant="success" class="px-4 py-2 text-sm hidden">
                    <i class="fa-solid fa-check mr-1"></i> Confirm Received
                </x-button>

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
        <x-button variant="secondary" iconName="printer" class="btn-action btn-print h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="print" title="Open printable slip">Print</x-button>
    </template>
    <template id="btn-return-template">
        <x-button variant="secondary" iconName="arrow-uturn-left" class="btn-action btn-utility h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="return" title="Start return">Return Items</x-button>
    </template>

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
        <template data-status="delivered"><x-status-badge type="success" text="Delivered" /></template>

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
                const statusMatches = !statusValue || statusText.includes(statusValue);
                
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
