<x-app-layout>
    <div class="p-6">
        <x-title level="h2"
            size="2xl"
            weight="bold"
            icon="clipboard-document-check"
            iconStyle="plain"
            iconColor="gov-accent"> Borrow Requests </x-title>

        <!-- Alerts -->
        <div id="adminAlertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>

        <!-- Borrow Requests Table -->
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm text-center text-gray-600 shadow-sm border rounded-lg overflow-hidden">
                <thead class="bg-purple-600 text-white text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Borrower</th>
                        <th class="px-6 py-3">Request ID</th>
                        <th class="px-6 py-3">Borrow Date</th>
                        <th class="px-6 py-3">Return Date</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="borrowRequestsTableBody" class="divide-y bg-white">
                    <tr>
                        <td colspan="6" class="py-4 text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <nav id="paginationNav" class="inline-flex items-center space-x-2"></nav>
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

<!-- Rejection Reason Modal -->
<x-modal name="rejectionReasonModal" maxWidth="md">
    <div class="p-6 space-y-6" id="rejectionReasonModalPanel">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Select Rejection Reason</h3>
                <p class="text-sm text-gray-600 mt-1">Choose the main reason for rejecting this request:</p>
            </div>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition"
                @click="$dispatch('close-modal','rejectionReasonModal'); window.dispatchEvent(new CustomEvent('rejection-flow-reset'));"
            >
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="space-y-3">
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-purple-400 cursor-pointer transition">
                <input type="radio" name="rejectionReason" value="Incomplete" class="text-purple-600 focus:ring-purple-500" />
                <span class="text-sm text-gray-700 font-medium">Incomplete</span>
            </label>
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-purple-400 cursor-pointer transition">
                <input type="radio" name="rejectionReason" value="Invalid" class="text-purple-600 focus:ring-purple-500" />
                <span class="text-sm text-gray-700 font-medium">Invalid</span>
            </label>
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-purple-400 cursor-pointer transition">
                <input type="radio" name="rejectionReason" value="Duplicate" class="text-purple-600 focus:ring-purple-500" />
                <span class="text-sm text-gray-700 font-medium">Duplicate</span>
            </label>
            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-purple-400 cursor-pointer transition">
                <input type="radio" name="rejectionReason" value="Other" class="text-purple-600 focus:ring-purple-500" />
                <span class="text-sm text-gray-700 font-medium">Other</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <x-button id="rejectionReasonCancelBtn" variant="secondary" class="px-4 py-2 text-sm">
                Cancel
            </x-button>
            <x-button id="rejectionReasonConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                Confirm
            </x-button>
        </div>
    </div>
</x-modal>

<!-- Custom Rejection Modal -->
<x-modal name="customRejectionModal" maxWidth="md">
    <div class="p-6 space-y-6" id="customRejectionModalPanel">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Custom Rejection Reason</h3>
                <p class="text-sm text-gray-600 mt-1">Please provide a detailed reason for rejecting this item:</p>
            </div>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition"
                @click="$dispatch('close-modal','customRejectionModal'); window.dispatchEvent(new CustomEvent('rejection-flow-reset'));"
            >
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label for="customRejectionSubject" class="text-sm font-medium text-gray-700">Subject</label>
                <input
                    type="text"
                    id="customRejectionSubject"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500"
                    placeholder="Brief subject line..."
                />
            </div>
            <div>
                <label for="customRejectionDetails" class="text-sm font-medium text-gray-700">Detailed Reason</label>
                <textarea
                    id="customRejectionDetails"
                    rows="4"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500"
                    placeholder="Enter detailed rejection reason..."
                ></textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <x-button id="customRejectionBackBtn" variant="secondary" class="px-4 py-2 text-sm">
                &larr; Back
            </x-button>

            <x-button id="customRejectionConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                Confirm Reject
            </x-button>
        </div>
    </div>
</x-modal>

<!-- Letter Preview Modal -->
<x-modal name="letterPreviewModal" maxWidth="3xl">
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">Uploaded Support Letter</h3>
                <p class="text-sm text-gray-600 mt-1">Preview the submitted signed support letter.</p>
            </div>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition"
                @click="$dispatch('close-modal','letterPreviewModal')"
            >
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden min-h-[360px] flex items-center justify-center">
            <img id="letterPreviewImage" src="" alt="Letter preview" class="max-h-[520px] w-auto hidden" />
            <iframe id="letterPreviewFrame" title="Letter preview frame" class="w-full h-[460px] hidden" loading="lazy"></iframe>
            <div id="letterPreviewPlaceholder" class="text-center text-gray-600 text-sm space-y-2">
                <p>Preview not available for this file type.</p>
                <a
                    id="letterPreviewDownloadPrimary"
                    href="#"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 text-purple-600 font-medium hover:text-purple-700 hidden"
                >
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    <span>Open letter in new tab</span>
                </a>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600">
                <span class="font-medium text-gray-800">File:</span>
                <span id="letterPreviewFilename">--</span>
            </div>
            <div class="flex items-center gap-3">
                <a
                    id="letterPreviewDownloadFooter"
                    href="#"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 text-purple-600 font-medium hover:text-purple-700 hidden"
                >
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    <span>Open in new tab</span>
                </a>
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal','letterPreviewModal')">
                    Close
                </x-button>
            </div>
        </div>
    </div>
</x-modal>

<!-- Deliver Items Modal -->
<x-modal name="deliverItemsModal" maxWidth="md">
    <div class="p-6 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div class="flex items-center gap-3">
                <i class="fas fa-truck text-indigo-600 text-2xl"></i>
                <h3 class="text-lg font-semibold text-gray-900">Deliver Items</h3>
            </div>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition"
                @click="$dispatch('close-modal', 'deliverItemsModal')"
            >
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Item Information -->
        <div id="deliverItemsInfo" class="space-y-3">
            <!-- Items will be populated by JavaScript -->
        </div>

        <!-- Reason Selection -->
        <div class="space-y-3 border-t border-gray-200 pt-4">
            <label class="text-sm font-medium text-gray-700">Reason for Delivery</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="deliveryReason" value="missing" class="text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-700">Missing</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="deliveryReason" value="damaged" class="text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-700">Damaged</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="deliveryReason" value="others" class="text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-700">Others</span>
                </label>
            </div>

            <!-- Others Reason Fields (hidden by default) -->
            <div id="deliverItemsOthersFields" class="hidden space-y-3 pt-2 border-t border-gray-100">
                <div>
                    <label for="deliveryReasonSubject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input
                        type="text"
                        id="deliveryReasonSubject"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter subject..."
                        maxlength="255"
                    />
                </div>
                <div>
                    <label for="deliveryReasonExplanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation</label>
                    <textarea
                        id="deliveryReasonExplanation"
                        rows="4"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter explanation..."
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'deliverItemsModal')">
                Cancel
            </x-button>
            <x-button id="deliverItemsConfirmBtn" variant="success" class="px-4 py-2 text-sm">
                Confirm
            </x-button>
        </div>
    </div>
</x-modal>

<!-- Assign Manpower + Quantity Adjustment Modal -->
<x-modal name="assignManpowerModal" maxWidth="2xl">
    <div class="p-6 space-y-5 max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <h3 class="text-xl font-semibold text-gray-900">Assign Manpower & Adjust Quantities</h3>
                <p class="text-sm text-gray-500">Review the requested resources, adjust where needed, and capture a quick reason whenever reductions are applied.</p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="$dispatch('close-modal','assignManpowerModal')">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form id="assignManpowerForm" class="space-y-5" onsubmit="return false;">
            <input type="hidden" id="assignManpowerRequestId" />

            <div class="grid gap-4 md:grid-cols-[2fr,3fr]">
                <div class="space-y-2">
                    <label class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Delivery Location</label>
                    <div id="assignManpowerLocation" class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">--</div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Uploaded Letter</label>
                    <div
                        id="assignLetterPreviewWrapper"
                        class="group flex flex-col items-center justify-center border border-dashed border-gray-300 rounded-lg bg-white p-3 min-h-[160px] cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition"
                        role="button"
                        tabindex="0"
                        aria-disabled="true"
                        data-letter-url=""
                        data-letter-name=""
                        data-letter-type=""
                    >
                        <img
                            id="assignLetterPreview"
                            src=""
                            alt="Uploaded letter preview"
                            class="max-h-48 object-contain rounded shadow hidden"
                        />
                        <div class="text-center space-y-2">
                            <span id="assignLetterFallback" class="text-sm text-gray-500 block">No letter uploaded</span>
                            <span id="assignLetterHint" class="hidden text-xs font-medium text-purple-600">Click to preview</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-[1fr,1fr]">
                <div class="space-y-1">
                    <label for="assignManpowerInput" class="text-sm font-medium text-gray-700 flex items-center justify-between">
                        <span>Manpower Needed</span>
                        <span class="text-xs text-gray-500">Requested: <span id="assignRequestedTotal">--</span></span>
                    </label>
                    <input
                        id="assignManpowerInput"
                        type="number"
                        min="0"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500"
                    />
                </div>
                <div id="assignManpowerReasonWrapper" class="space-y-1 hidden">
                    <label for="assignManpowerReason" class="text-sm font-medium text-gray-700">Reason for manpower reduction</label>
                    <select
                        id="assignManpowerReason"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500">
                        <option value="">Select reason</option>
                        <option value="Task completion">Task completion</option>
                        <option value="Overestimated need">Overestimated need</option>
                        <option value="Schedule conflict">Schedule conflict</option>
                    </select>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Borrowed Items</h4>
                    <span class="text-xs text-gray-400">Adjust quantities only if necessary.</span>
                </div>
                <div id="assignManpowerItemsContainer" class="space-y-3 max-h-72 overflow-auto border border-gray-200 rounded-lg bg-white p-3">
                    <!-- JS will populate rows -->
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal','assignManpowerModal')">Cancel</x-button>
                <x-button id="assignManpowerConfirmBtn" variant="success">Save &amp; Approve</x-button>
            </div>
        </form>
    </div>
</x-modal>
    {{-- Button templates (used by JS to create action buttons per-row) --}}
    <template id="btn-view-template">
        <x-button variant="secondary" iconName="eye" class="px-2 py-1 text-xs" data-action="view">View</x-button>
    </template>
    <template id="btn-validate-template">
        <x-button variant="success" iconName="check" class="px-2 py-1 text-xs" data-action="accept">Validate</x-button>
    </template>
    <template id="btn-reject-template">
        <x-button variant="danger" iconName="x-circle" class="px-2 py-1 text-xs" data-action="reject">Reject</x-button>
    </template>
    <template id="btn-deliver-template">
        <x-button variant="info" iconName="truck" class="px-2 py-1 text-xs" data-action="deliver">Deliver Items</x-button>
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

    @vite(['resources/js/app.js'])

</x-app-layout>


