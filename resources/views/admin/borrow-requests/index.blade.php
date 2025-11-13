<x-app-layout>
    @php
        $noMainScroll = false; // Enable main content scrolling since we removed table scrollbar
    @endphp

    <!-- Title and Actions Section -->
    <div class="py-2">
        <div class="px-2">
            <!-- Alerts -->
            <div id="adminAlertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>
            
            <!-- Title Row -->
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2"
                                size="2xl"
                                weight="bold"
                                icon="clipboard-document-check"
                                iconStyle="plain"
                                iconColor="gov-accent"
                                compact="true"> Borrow Requests </x-title>
                    </div>
                    
                    <!-- Search Bar and Sort By -->
                    <div class="flex items-center gap-3">
                        <!-- Live Search Bar -->
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text"
                                   id="borrow-requests-live-search"
                                   placeholder="Search Borrower and Request"
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        
                        <!-- Sort By Status (no dropdown arrow like Reports) -->
                        <div class="flex-shrink-0 relative">
                            <select id="borrow-requests-status-filter"
                                    class="border border-gray-300 rounded-lg pl-4 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400 appearance-none bg-white">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="validated">Validated</option>
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
                            <th class="px-6 py-3 text-center">Borrower</th>
                            <th class="px-6 py-3 text-center">Request ID</th>
                            <th class="px-6 py-3 text-center">Borrow Date</th>
                            <th class="px-6 py-3 text-center">Return Date</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="borrowRequestsTableBody" class="text-center">
                        <tr>
                            <td colspan="6" class="py-4 text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal (modern design) -->
    <x-modal name="requestDetailsModal" maxWidth="lg">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-clipboard-check text-purple-600"></i>
                    <span id="requestTitle">Borrow Request Details</span>
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'requestDetailsModal')"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Status banner -->
            <div id="requestStatusBanner" class="flex items-center gap-3 bg-purple-50 border border-purple-100 rounded-lg p-3">
                <i class="fas fa-check-circle text-green-600"></i>
                <span id="requestShortStatus" class="font-semibold text-purple-800">Borrow Request #</span>
            </div>

            <!-- Content blocks -->
            <div id="requestDetailsModalContent" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                <!-- Borrower -->
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-user text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrower</div>
                        <div class="text-gray-600" id="borrowerName">—</div>
                    </div>
                </div>

                <!-- Manpower (NEW) -->
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-users text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Number of Manpower</div>
                        <div class="text-gray-600" id="manpowerCount">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-map-marker-alt text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Delivery Location</div>
                        <div class="text-gray-600" id="requestLocation">—</div>
                    </div>
                </div>

                <!-- Status (badge) -->
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-tag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Status</div>
                        <div id="statusBadge" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">—</div>
                    </div>
                </div>

                <!-- Items -->
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-box-open text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Items Requested</div>
                        <div class="text-gray-600" id="itemsList">—</div>
                    </div>
                </div>

                <!-- Rejection Reason -->
                <div id="rejectionReasonCard" class="hidden flex items-start gap-3 bg-red-50 border border-red-100 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-circle-xmark text-red-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-red-700">Rejection Reason</div>
                        <div class="mt-1 space-y-1">
                            <div class="text-sm font-semibold text-gray-900" id="rejectionReasonSubject"></div>
                            <p class="text-sm text-gray-600 whitespace-pre-line" id="rejectionReasonDetail"></p>
                        </div>
                    </div>
                </div>

                <!-- Borrow Date -->
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-day text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrow Date</div>
                        <div class="text-gray-600" id="borrowDate">—</div>
                    </div>
                </div>

                <!-- Return Date -->
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return Date</div>
                        <div class="text-gray-600" id="returnDate">—</div>
                    </div>
                </div>

                <!-- Status info -->
                <div class="flex items-start gap-3 bg-blue-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Status Information</div>
                        <div class="text-gray-600" id="statusInfo">—</div>
                    </div>
                </div>
            </div>

            <!-- Footer button -->
            <div class="flex justify-end pt-4 border-t border-gray-200">
                <x-button
                    variant="secondary"
                    iconName="x-circle"
                    class="px-4 py-2 text-sm"
                    @click="$dispatch('close-modal', 'requestDetailsModal')"
                >
                    Close
                </x-button>
            </div>
        </div>
    </x-modal>

    <!-- Confirmation modal for Accept / Reject (icon vertically centered now) -->
    <!-- Confirmation modal for Accept / Reject -->
<x-modal name="confirmActionModal" maxWidth="md">
    <div class="p-6">
        <!-- Icon + title on one line -->
        <div class="flex items-center gap-3">
            <i id="confirmActionIcon" class="fas fa-exclamation-circle text-yellow-500 text-2xl"></i>
            <h3 id="confirmActionTitle" class="text-lg font-semibold text-gray-900">Confirm Action</h3>
        </div>

        <!-- Explanatory message BELOW the title with a little breathing room -->
        <p id="confirmActionMessage" class="mt-3 text-sm text-gray-600">
            Are you sure?
        </p>

        <!-- Footer -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-4">
            <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'confirmActionModal')">
                Cancel
            </x-button>

            <x-button id="confirmActionConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                Confirm
            </x-button>
        </div>
    </div>
    </x-modal>

    <!-- Rejection Reason Select Modal -->
    <x-modal name="rejectReasonSelectModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Select Rejection Reason</h3>
                    <p class="text-sm text-gray-500 mt-1">Choose from saved reasons or create a new one.</p>
                </div>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'rejectReasonSelectModal')"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div id="rejectReasonOptions" class="space-y-3 max-h-64 overflow-y-auto pr-1"></div>

            <div class="border-t border-gray-200 pt-4 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" name="rejectReasonChoice" value="__other__" id="rejectReasonOtherOption" class="text-purple-600 focus:ring-purple-500">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Other</div>
                        <p class="text-xs text-gray-500">Create a new rejection reason</p>
                    </div>
                </label>
                <button type="button" id="rejectReasonCreateNewBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    Create New &rarr;
                </button>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <x-button id="rejectReasonSelectCancelBtn" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'rejectReasonSelectModal')">
                    Cancel
                </x-button>
                <x-button id="rejectReasonSelectConfirmBtn" variant="danger" class="px-4 py-2 text-sm" disabled>
                    Confirm Reject
                </x-button>
            </div>
        </div>
    </x-modal>

    <!-- Rejection Reason Custom Modal -->
    <x-modal name="rejectReasonCustomModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex flex-col gap-1">
                <h3 class="text-lg font-semibold text-gray-900">Custom Rejection Reason</h3>
                <p class="text-sm text-gray-500">Provide a subject and detailed explanation for rejecting this request.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="rejectReasonSubjectInput" class="block text-sm font-medium text-gray-700">Subject</label>
                    <input
                        type="text"
                        id="rejectReasonSubjectInput"
                        class="mt-1 block w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm"
                        placeholder="e.g. Incomplete requirements"
                    >
                </div>
                <div>
                    <label for="rejectReasonDetailInput" class="block text-sm font-medium text-gray-700">Detailed Reason</label>
                    <textarea
                        id="rejectReasonDetailInput"
                        rows="4"
                        class="mt-1 block w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-sm"
                        placeholder="Share the specific reason for rejecting this request."
                    ></textarea>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <x-button id="rejectReasonCustomBackBtn" variant="secondary" class="px-4 py-2 text-sm">
                    Back
                </x-button>
                <x-button id="rejectReasonCustomConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                    Confirm Reject
                </x-button>
            </div>
        </div>
    </x-modal>

    <!-- Rejection Reason View Modal -->
    <x-modal name="rejectReasonViewModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex items-start justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Rejection Reason Details</h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'rejectReasonViewModal')"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Subject</p>
                    <p class="text-sm font-semibold text-gray-900" id="rejectReasonViewSubject"></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Detailed Reason</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line" id="rejectReasonViewDetail"></p>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'rejectReasonViewModal')">
                    Close
                </x-button>
            </div>
        </div>
    </x-modal>

    <!-- Rejection Reason Delete Modal -->
    <x-modal name="rejectReasonDeleteModal" maxWidth="sm">
        <div class="p-6 space-y-5">
            <div class="flex items-start gap-3">
                <i class="fas fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Removal</h3>
                    <p class="text-sm text-gray-600 mt-1">Are you sure you want to remove this rejection reason?</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                <p class="text-sm font-semibold text-gray-800" id="rejectReasonDeleteName"></p>
                <p class="text-xs text-gray-500 mt-1" id="rejectReasonDeleteUsage"></p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-button id="rejectReasonDeleteCancelBtn" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'rejectReasonDeleteModal')">
                    Cancel
                </x-button>
                <x-button id="rejectReasonDeleteConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                    Remove
                </x-button>
            </div>
        </div>
    </x-modal>

<!-- Validate Request Modal -->
<x-modal name="assignManpowerModal" maxWidth="2xl">
    <div class="p-6 space-y-5" x-data="{ showLargeImage: false, largeImageSrc: '' }">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <h3 class="text-xl font-semibold text-gray-900">Validate Request</h3>
                <p class="text-sm text-gray-500">Review the uploaded letter before validating the request.</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="$dispatch('close-modal','assignManpowerModal')">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form id="assignManpowerForm" class="space-y-5" onsubmit="return false;">
            <input type="hidden" id="assignManpowerRequestId" />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Delivery Location</label>
                        <div id="assignManpowerLocation" class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">--</div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Borrow Period</label>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="font-medium text-gray-600 block mb-1">Borrow Date:</span>
                                    <span id="assignBorrowDate" class="text-gray-800 font-semibold">--</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600 block mb-1">Return Date:</span>
                                    <span id="assignReturnDate" class="text-gray-800 font-semibold">--</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Requested Items</label>
                        <div id="assignItemsList" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 max-h-40 overflow-y-auto">
                            <ul class="list-disc pl-4 space-y-1 text-sm text-gray-800">
                                <!-- Items will be populated by JavaScript -->
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Uploaded Letter</label>
                    <div id="assignLetterPreviewWrapper" class="flex items-center justify-center border border-dashed border-gray-300 rounded-lg bg-white p-3 min-h-[200px]">
                        <img id="assignLetterPreview" 
                             src="" 
                             alt="Uploaded letter" 
                             class="max-w-full max-h-[400px] object-contain rounded shadow hidden cursor-pointer hover:opacity-90 transition-opacity" 
                             @click="largeImageSrc = $el.src; showLargeImage = true"
                             title="Click to view full size" />
                        <span id="assignLetterFallback" class="text-sm text-gray-500">No letter uploaded</span>
                    </div>
                    <p class="text-xs text-gray-400 text-center">Click image to view full size</p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal','assignManpowerModal')">Cancel</x-button>
                <x-button id="assignManpowerConfirmBtn" variant="success">Validate</x-button>
            </div>
        </form>

        <!-- Image Lightbox Overlay -->
        <div x-show="showLargeImage" 
             x-cloak
             @click.self="showLargeImage = false"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-75 p-4"
             style="display: none;">
            <div class="relative max-w-7xl max-h-full">
                <!-- Close Button -->
                <button @click="showLargeImage = false" 
                        class="absolute -top-12 right-0 bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm text-white rounded-full p-2 transition-all hover:scale-110 shadow-lg"
                        aria-label="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <!-- Large Image -->
                <img :src="largeImageSrc" 
                     alt="Letter full view" 
                     class="max-w-full max-h-[90vh] object-contain rounded shadow-2xl"
                     @click.stop />
            </div>
        </div>
    </div>
</x-modal>
    {{-- Button templates (used by JS to create action buttons per-row) --}}
    <template id="btn-view-template">
        <x-button variant="secondary" iconName="eye" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only !bg-emerald-500 hover:!bg-emerald-600 text-white" data-action="view">View</x-button>
    </template>
    <template id="btn-validate-template">
        <x-button variant="primary" iconName="shield-check" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="validate">Validate</x-button>
    </template>
    <template id="btn-reject-template">
        <x-button variant="danger" iconName="x-circle" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="reject">Reject</x-button>
    </template>
    <template id="btn-deliver-template">
        <x-button variant="info" iconName="truck" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="deliver">Deliver Items</x-button>
    </template>
    <template id="btn-mark-delivered-template">
        <x-button variant="success" iconName="check-circle" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="mark-delivered">Mark as Delivered</x-button>
    </template>
    <template id="btn-cancel-dispatch-template">
        <x-button variant="danger" iconName="arrow-uturn-left" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="cancel-dispatch">Cancel Dispatch</x-button>
    </template>
    <template id="btn-accept-template">
        <x-button variant="success" iconName="check-circle" class="h-10 w-10 !px-0 !py-0 rounded-full shadow [&>span:first-child]:mr-0 [&>span:last-child]:sr-only" data-action="approve">Accept</x-button>
    </template>

    {{-- Alert templates --}}
    <template id="alert-success-template">
        <div class="max-w-xs">
            <x-alert type="success"><span data-alert-message></span></x-alert>
        </div>
    </template>
    <template id="alert-error-template">
        <div class="max-w-xs">
            <x-alert type="error"><span data-alert-message></span></x-alert>
        </div>
    </template>
    <template id="alert-info-template">
        <div class="max-w-xs">
            <x-alert type="info"><span data-alert-message></span></x-alert>
        </div>
    </template>
    <template id="alert-warning-template">
        <div class="max-w-xs">
            <x-alert type="warning"><span data-alert-message></span></x-alert>
        </div>
    </template>

    <!-- Pass small bootstrap variables to JS module -->
    <script>
        window.CSRF_TOKEN = "{{ csrf_token() }}";
        window.LIST_ROUTE = "{{ route('admin.borrow.requests.list') }}";
    </script>

    <script>
    // Live search and filter functionality for Borrow Requests
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('borrow-requests-live-search');
        const statusFilter = document.getElementById('borrow-requests-status-filter');
        const tableBody = document.getElementById('borrowRequestsTableBody');
        
        if (!searchInput || !statusFilter || !tableBody) return;
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const statusValue = statusFilter.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr[data-request-id]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const borrowerCell = row.querySelector('td:nth-child(1)');
                const requestIdCell = row.querySelector('td:nth-child(2)');
                const statusCell = row.querySelector('td:nth-child(5)');
                
                if (!borrowerCell || !requestIdCell || !statusCell) return;
                
                const borrowerText = borrowerCell.textContent.toLowerCase();
                const requestIdText = requestIdCell.textContent.toLowerCase();
                const statusText = statusCell.textContent.toLowerCase();
                
                // Check search match
                const searchMatches = borrowerText.includes(searchTerm) || requestIdText.includes(searchTerm);
                
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
                let noResultsRow = document.getElementById('no-results-row-borrow');
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row-borrow';
                    noResultsRow.innerHTML = `
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-medium">No requests found</p>
                                <p class="text-sm">Try adjusting your search or filter</p>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(noResultsRow);
                }
            } else {
                const noResultsRow = document.getElementById('no-results-row-borrow');
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
            this.placeholder = 'Search Borrower and Request';
        });
    });
    </script>

    @vite(['resources/js/app.js'])

</x-app-layout>


