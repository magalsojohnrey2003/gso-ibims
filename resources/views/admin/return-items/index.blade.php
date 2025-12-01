<x-app-layout>
    @php
        $noMainScroll = false; // Enable main content scrolling since we removed table scrollbar
    @endphp

    <!-- Title and Actions Section -->
    <div class="py-2">
        <div class="px-2">
            <div id="alertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]" aria-live="assertive"></div>
            
            <!-- Title Row -->
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex-shrink-0 flex items-center">
                        <x-title
                            level="h2"
                            size="2xl"
                            weight="bold"
                            icon="arrow-path-rounded-square"
                            iconStyle="plain"
                            iconColor="title-purple"
                            compact="true">
                            Return Items
                        </x-title>
                    </div>
                    
                    <!-- Search Bar and Sort By -->
                    <div class="flex items-center gap-3">
                        <!-- Live Search Bar -->
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text"
                                id="return-items-live-search"
                                placeholder="Search borrower or request ID"
                                   class="gov-input pl-12 pr-4 py-2.5 text-sm w-64 transition duration-200 focus:outline-none focus:ring-0" />
                        </div>
                        
                        <!-- Sort By Status -->
                        <div class="flex-shrink-0 relative">
                                    <select id="return-items-status-filter"
                                        class="gov-input pl-4 pr-4 py-2.5 text-sm transition duration-200 appearance-none focus:outline-none focus:ring-0">
                                <option value="">All Status</option>
                                <option value="borrowed">Borrowed</option>
                                <option value="returned">Returned</option>
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
                            <th class="px-6 py-3 text-center">Borrower</th>
                            <th class="px-6 py-3 text-center">Request Type</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="returnItemsTableBody" class="text-center">
                        <x-table-loading-state colspan="5" />
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <template id="return-items-empty-state-template">
        <x-table-empty-state colspan="5" />
    </template>

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
    <template id="badge-status-dispatched">
        <x-status-badge type="info" text="Borrowed" />
    </template>
    <template id="badge-status-delivered">
        <x-status-badge type="delivered" text="Delivered" />
    </template>
    <template id="badge-status-returned">
        <x-status-badge type="success" text="Returned" />
    </template>
    <template id="badge-status-missing">
        <x-status-badge type="danger" text="Missing" />
    </template>
    <template id="badge-status-damage">
        <x-status-badge type="rejected" text="Damaged" />
    </template>
    <template id="badge-status-minor_damage">
        <x-status-badge type="warning" text="Minor Damage" />
    </template>
    <template id="badge-status-rejected">
        <x-status-badge type="rejected" text="Rejected" />
    </template>
    <template id="badge-status-not_received">
        <x-status-badge type="warning" text="Not Received" />
    </template>

    <template id="badge-condition-good">
        <x-status-badge type="success" text="Good" icon="fa-check" />
    </template>
    <template id="badge-condition-missing">
        <x-status-badge type="danger" text="Missing" icon="fa-question-circle" class="bg-orange-100 text-orange-700 ring-orange-200" />
    </template>
    <template id="badge-condition-damage">
        <x-status-badge type="danger" text="Damage" icon="fa-exclamation-triangle" class="bg-rose-100 text-rose-700 ring-rose-200" />
    </template>
    <template id="badge-condition-minor_damage">
        <x-status-badge type="warning" text="Minor Damage" icon="fa-exclamation-circle" class="bg-amber-100 text-amber-700 ring-amber-200" />
    </template>
    <template id="badge-condition-pending">
        <x-status-badge type="gray" text="Pending" />
    </template>

    <template id="badge-request-type-walkin">
        <x-status-badge type="info" text="Walk-in" />
    </template>
    <template id="badge-request-type-online">
        <x-status-badge type="primary" text="Online" />
    </template>

    <template id="alert-success-template">
        <x-alert type="success"><span data-alert-message></span></x-alert>
    </template>
    <template id="alert-error-template">
        <x-alert type="error"><span data-alert-message></span></x-alert>
    </template>

    <template id="action-manage-template">
        <x-button data-action="manage" variant="secondary" iconName="cog-6-tooth" class="btn-action btn-utility h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" title="Manage return">
            Manage
        </x-button>
    </template>

    <template id="action-collect-template">
        <x-button data-action="collect" variant="secondary" iconName="clipboard-document-check" class="btn-action btn-accept h-10 w-10 [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" title="Mark as collected">
            Mark as Collected
        </x-button>
    </template>

    <!-- Manage Modal -->
    <x-modal name="manageReturnItemsModal" maxWidth="3xl">
        <div class="flex flex-col max-h-[90vh] bg-white">
            <div class="px-6 pt-6 pb-4 border-b border-purple-700 sticky top-0 bg-purple-600 text-white z-10">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-xl font-semibold text-white flex items-center gap-3">
                            <i class="fa-solid fa-screwdriver-wrench text-white"></i>
                            <span>Manage Return</span>
                        </h3>
                        <p class="mt-1 text-sm text-purple-100">Review items and update their condition after collection.</p>
                    </div>
                    <button
                        type="button"
                        class="text-white/80 hover:text-white transition"
                        @click="$dispatch('close-modal', 'manageReturnItemsModal')"
                        aria-label="Close dialog">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 py-4 space-y-5 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                        <i class="fas fa-hashtag text-purple-600 mt-1"></i>
                        <div>
                            <div class="font-medium text-gray-800">Request ID</div>
                            <div class="text-gray-600" id="manage-request-id">--</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                        <i class="fas fa-user text-purple-600 mt-1"></i>
                        <div>
                            <div class="font-medium text-gray-800">Borrower</div>
                            <div class="text-gray-600" id="manage-borrower">--</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 md:col-span-2">
                        <i class="fas fa-map-marker-alt text-purple-600 mt-1"></i>
                        <div class="w-full">
                            <div class="font-medium text-gray-800">Address</div>
                            <div class="text-gray-600" id="manage-address">--</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                        <i class="fas fa-tag text-purple-600 mt-1"></i>
                        <div>
                            <div class="font-medium text-gray-800">Status</div>
                            <div id="manage-status-badge" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">--</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                        <i class="fas fa-clock text-purple-600 mt-1"></i>
                        <div>
                            <div class="font-medium text-gray-800">Return Timestamp</div>
                            <div class="text-gray-600" id="manage-return-timestamp">--</div>
                        </div>
                    </div>
                </div>

                <div id="manage-item-filter-wrapper" class="space-y-2 hidden">
                    <label for="manage-item-filter" class="text-sm font-semibold text-gray-700">Borrowed Item</label>
                    <select id="manage-item-filter" class="block w-full rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500"></select>
                </div>

                <!-- Bulk Selection and Sorting Controls -->
                <div class="flex flex-wrap items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="manage-enable-selection" class="w-4 h-4 text-purple-600 border-2 border-purple-300 bg-white rounded shadow focus:ring-purple-500" />
                            <span class="text-sm font-medium text-gray-700">Select</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <select id="manage-bulk-condition" class="rounded-lg border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                                <option value="">Select Condition</option>
                                <option value="good">Good</option>
                                <option value="minor_damage">Minor Damage</option>
                                <option value="damage">Damage</option>
                                <option value="missing">Missing</option>
                            </select>
                            <button id="manage-bulk-update-btn" type="button" disabled class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-purple-600 text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-300 disabled:opacity-50 disabled:cursor-not-allowed">
                                Update
                            </button>
                        </div>
                    </div>
                    <div class="relative w-full md:w-auto md:min-w-[240px] md:ml-auto">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        <input type="text" id="manage-items-search" placeholder="Search property number" autocomplete="off" class="gov-input pl-9 pr-4 py-2.5 text-sm w-full transition duration-200 focus:outline-none focus:ring-0" />
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg">
            <div class="table-wrapper">
                <div class="table-container" style="max-height: 16rem;">
                    <table class="w-full text-sm text-gray-700 gov-table">
                            <thead class="bg-gray-100 text-xs uppercase text-gray-600 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-2 text-left w-12" id="manage-checkbox-header" style="display: none;">
                                        <span class="sr-only">Select</span>
                                    </th>
                                    <th class="px-4 py-2 text-left">Property Number</th>
                                    <th class="px-4 py-2 text-left">Item Name</th>
                                    <th class="px-4 py-2 text-left">Condition</th>
                                </tr>
                            </thead>
                            <tbody id="manage-items-tbody" class="divide-y bg-white">
                                <x-table-loading-state colspan="4" />
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'manageReturnItemsModal')">
                    Close
                </x-button>
            </div>
        </div>
    </x-modal>

    <!-- Collect Confirmation Modal -->
    <x-modal name="collectConfirmModal" maxWidth="sm" background="transparent">
        <div class="overflow-hidden rounded-3xl bg-white shadow-xl">
            <div class="flex items-start gap-4 px-6 py-6 bg-gradient-to-r from-purple-600 to-purple-500 text-white">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20">
                    <i class="fas fa-clipboard-check text-xl"></i>
                </span>
                <div class="space-y-1">
                    <h3 class="text-xl font-semibold">Confirm Collection</h3>
                    <p class="text-sm text-purple-100" id="collectConfirmMessage">Are you sure this borrow request has been picked up?</p>
                </div>
            </div>
            <div class="px-6 py-6 space-y-5">
                <div id="collectItemsWrapper" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Borrowed Items</p>
                        <span id="collectItemsCount" class="text-xs font-medium text-purple-600 bg-purple-100 px-2.5 py-1 rounded-full">0 items</span>
                    </div>
                    <ul id="collectItemsList" class="max-h-56 overflow-y-auto rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 space-y-2">
                        <li class="text-sm text-gray-500">Select a request to view borrowed items.</li>
                    </ul>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                <x-button variant="secondary" type="button" class="px-5" @click="$dispatch('close-modal', 'collectConfirmModal')">
                    Cancel
                </x-button>
                <x-button type="button" class="px-5" id="collectConfirmBtn">
                    Confirm
                </x-button>
            </div>
        </div>
    </x-modal>

    <script>
        window.RETURN_ITEMS_CONFIG = {
            list: "{{ route('admin.return-items.list') }}",
            base: "{{ url('/admin/return-items') }}",
            updateInstanceBase: "{{ url('/admin/return-items/instances') }}",
            collectBase: "{{ url('/admin/return-items') }}",
            csrf: "{{ csrf_token() }}"
        };
    </script>

    <script>
    // Live search and filter functionality for Return Items
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('return-items-live-search');
        const statusFilter = document.getElementById('return-items-status-filter');
        const tableBody = document.getElementById('returnItemsTableBody');
        
        if (!searchInput || !statusFilter || !tableBody) return;
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const statusValue = statusFilter.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr[data-request-code]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const borrowerCell = row.querySelector('td[data-column="borrower"]') || row.querySelector('td:nth-child(2)');
                const statusCell = row.querySelector('td[data-column="status"]') || row.querySelector('td:nth-child(4)');
                const requestCode = row.getAttribute('data-request-code');
                
                if (!borrowerCell || !statusCell) return;
                
                const borrowerText = borrowerCell.textContent.toLowerCase();
                const requestCodeText = (requestCode || '').toLowerCase();
                const statusText = statusCell.textContent.toLowerCase();
                
                // Check search match
                const searchMatches = borrowerText.includes(searchTerm) || requestCodeText.includes(searchTerm);
                
                // Check status filter match (map "dispatched" to "borrowed")
                const statusMatches = !statusValue || statusText.includes(statusValue) || (statusValue === 'borrowed' && statusText.includes('borrowed'));
                
                // Show/hide row
                if (searchMatches && statusMatches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Handle empty state
            const loadingRow = tableBody.querySelector('tr:not([data-request-code])');
            if (visibleCount === 0 && rows.length > 0 && !loadingRow) {
                let noResultsRow = document.getElementById('no-results-row-return');
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row-return';
                    noResultsRow.innerHTML = `
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-medium">No returns found</p>
                                <p class="text-sm">Try adjusting your search or filter</p>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(noResultsRow);
                }
            } else {
                const noResultsRow = document.getElementById('no-results-row-return');
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
            this.placeholder = 'Search borrower or request ID';
        });
    });
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
