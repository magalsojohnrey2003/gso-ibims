<x-app-layout> 
    <div class="p-6">
            <x-title level="h2"
                size="2xl"
                weight="bold"
                icon="clipboard-document-check"
                variant="s"
                iconStyle="plain"
                iconColor="gov-accent"> My Borrowed Items </x-title>

        <!-- Alerts -->
        <div id="userAlertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center text-gray-600 shadow-sm border rounded-lg overflow-hidden">
                <thead class="bg-purple-600 text-white text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Request ID</th>
                        <th class="px-6 py-3">Borrow Date</th>
                        <th class="px-6 py-3">Return Date</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody id="myBorrowedItemsTableBody" class="divide-y bg-white">
                    <tr>
                        <td colspan="5" class="py-4 text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <nav id="paginationNav" class="inline-flex items-center space-x-2"></nav>
        </div>
    </div>

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
                        <div id="mbi-status-badge" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">—</div>
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
        <x-button iconName="eye" variant="secondary" class="px-2 py-1 text-xs" data-action="view">View</x-button>
    </template>

    <template id="btn-print-template">
        <x-button variant="secondary" iconName="printer" class="px-2 py-1 text-xs" data-action="print">Print</x-button>
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
        <template data-status="qr_verified"><x-status-badge type="qr" text="QR Verified" /></template>
        <template data-status="default"><x-status-badge type="gray" text="—" /></template>
        <template data-status="validated"><x-status-badge type="info" text="Validated" /></template>

    </div>

    <!-- Small bootstrap variables for the module -->
    <script>
        // IMPORTANT: these must match the route names in your web.php
        window.LIST_ROUTE = "{{ route('user.borrowed.items.list') }}";    // GET JSON list
        window.CSRF_TOKEN = "{{ csrf_token() }}";

        // Helper to pull badge HTML from the hidden templates
        window.renderStatusBadge = function(status) {
            const key = (status || '').toLowerCase();
            const tpl = document.querySelector(`#statusBadgeTemplates template[data-status="${key}"]`)
                      || document.querySelector('#statusBadgeTemplates template[data-status="default"]');
            return tpl ? tpl.innerHTML : `<span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">${status || '—'}</span>`;
        };
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
