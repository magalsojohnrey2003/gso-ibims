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
                        <x-title level="h2"
                                size="2xl"
                                weight="bold"
                                icon="clipboard-document-check"
                                variant="s"
                                iconStyle="plain"
                                iconColor="title-purple"
                                compact="true"> My Borrowed Items </x-title>
                    </div>
                    
                    <!-- Search Bar and Sort By -->
                    <div class="flex items-center gap-3">
                        <!-- Live Search Bar -->
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text"
                                   id="my-borrowed-items-live-search"
                                   placeholder="Search Request ID"
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        
                        <!-- Sort By Status -->
                        <div class="flex-shrink-0 relative">
                            <select id="my-borrowed-items-status-filter"
                                    class="border border-gray-300 rounded-lg pl-4 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400 appearance-none bg-white">
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

    <!-- Modal (same visual style as borrow-requests) -->
    <x-modal name="borrowDetailsModal" maxWidth="lg">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fa-solid fa-box text-purple-600"></i>
                    <span>Borrowed Item Details</span>
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'borrowDetailsModal')"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Status banner -->
            <div id="mbi-status-banner" class="flex items-center gap-3 bg-gray-50 border border-purple-100 rounded-lg p-3">
                <i class="fas fa-info-circle text-purple-600"></i>
                <span id="mbi-short-status" class="font-semibold text-purple-800">Borrow Request</span>
            </div>

            <!-- Content blocks -->
            <div id="mbi-modal-content" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-hashtag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Request ID</div>
                        <div class="text-gray-600" id="mbi-request-id">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-day text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrow Date</div>
                        <div class="text-gray-600" id="mbi-borrow-date">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-map-marker-alt text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Delivery Location</div>
                        <div class="text-gray-600" id="mbi-location">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return Date</div>
                        <div class="text-gray-600" id="mbi-return-date">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-tag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Status</div>
                        <div id="mbi-status-badge" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-box-open text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Items</div>
                        <ul id="mbi-items" class="list-disc list-inside text-gray-600">—</ul>
                    </div>
                </div>

                <div class="col-span-2 hidden" id="mbi-rejection-block">
                    <p class="text-red-600 font-semibold">⚠️ Rejection Reason</p>
                    <p id="mbi-rejection-reason" class="bg-red-50 text-red-800 p-2 rounded"></p>
                </div>

                <div class="col-span-2 hidden" id="mbi-delivery-reason-block">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="font-semibold text-indigo-800 mb-2 flex items-center gap-2">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Delivery Reason</span>
                                </div>
                                <div id="mbi-delivery-reason-content" class="text-sm text-indigo-700">
                                    <!-- Content will be populated by JavaScript -->
                                </div>
                            </div>
                            <button
                                type="button"
                                id="mbi-delivery-reason-toggle"
                                class="text-indigo-600 hover:text-indigo-800 text-xs font-medium flex items-center gap-1"
                            >
                                <span id="mbi-delivery-reason-toggle-text">Show more</span>
                                <i class="fas fa-chevron-down" id="mbi-delivery-reason-toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-2">
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
        <x-button iconName="eye" variant="secondary" class="btn-action btn-view h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="view">View</x-button>
    </template>

    <template id="btn-print-template">
        <x-button variant="secondary" iconName="printer" class="btn-action btn-print h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="print">Print</x-button>
    </template>
    <template id="btn-return-template">
        <x-button variant="secondary" iconName="arrow-uturn-left" class="btn-action btn-utility h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="return">Return Items</x-button>
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
