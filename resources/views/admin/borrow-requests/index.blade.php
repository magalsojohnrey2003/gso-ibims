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
                    <div id="assignLetterPreviewWrapper" class="flex items-center justify-center border border-dashed border-gray-300 rounded-lg bg-white p-3 min-h-[140px]">
                        <img id="assignLetterPreview" src="" alt="Uploaded letter" class="max-h-40 object-contain rounded shadow hidden" />
                        <span id="assignLetterFallback" class="text-sm text-gray-500">No letter uploaded</span>
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




