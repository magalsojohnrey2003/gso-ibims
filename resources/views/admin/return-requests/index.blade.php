{{-- resources/views/admin/return-requests.blade.php --}}
<x-app-layout>
    <div class="p-6">
        <x-title level="h2"
            size="2xl"
            weight="bold"
            icon="arrow-uturn-left"
            iconStyle="plain"
            iconColor="gov-accent"> Return Requests </x-title>

        <!-- Alerts container -->
        <div id="alertContainer" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>

        <div class="overflow-x-auto rounded-lg shadow">
            <table class="w-full text-sm text-center text-gray-600 shadow-sm border rounded-lg overflow-hidden">
                <thead class="bg-purple-600 text-white text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">Return ID</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Condition</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="requestsBody" class="divide-y bg-white">
                    <tr>
                        <td colspan="5" class="py-4 text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="mt-4 flex justify-center gap-2"></div>
    </div>

    <!-- Templates -->
    <template id="btn-view-template">
        <x-button data-action="view" variant="secondary" class="px-2 py-1 text-xs">
            <i class="fa-solid fa-eye mr-1"></i> View
        </x-button>
    </template>

    <template id="btn-accept-template">
        <x-button data-action="accept" variant="success" class="px-2 py-1 text-xs">
            <i class="fa-solid fa-check mr-1"></i> Accept
        </x-button>
    </template>

    <template id="btn-reject-template">
        <x-button data-action="reject" variant="danger" class="px-2 py-1 text-xs">
            <i class="fa-solid fa-xmark mr-1"></i> Reject
        </x-button>
    </template>

    <template id="badge-pending-template">
        <x-status-badge type="pending" text="pending" />
    </template>
    <template id="badge-approved-template">
        <x-status-badge type="accepted" text="approved" />
    </template>
    <template id="badge-rejected-template">
        <x-status-badge type="rejected" text="rejected" />
    </template>
    <template id="badge-return_pending-template">
        <x-status-badge type="info" text="return_pending" />
    </template>
    <template id="badge-returned-template">
        <x-status-badge type="success" text="returned" />
    </template>

    <template id="alert-success-template">
        <x-alert type="success"><span data-alert-message></span></x-alert>
    </template>
    <template id="alert-error-template">
        <x-alert type="error"><span data-alert-message></span></x-alert>
    </template>

    <template id="badge-condition-good-template">
        <x-status-badge type="success" text="good" />
    </template>
    <template id="badge-condition-fair-template">
        <x-status-badge type="info" text="fair" />
    </template>
    <template id="badge-condition-damaged-template">
        <x-status-badge type="danger" text="damaged" />
    </template>

    <!-- Modal -->
    <x-modal name="returnRequestModal" maxWidth="lg">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fa-solid fa-rotate-right text-purple-600"></i>
                    <span>Return Request Details</span>
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition"
                    @click="$dispatch('close-modal', 'returnRequestModal')"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div id="rrm-status-banner" class="flex items-center gap-3 bg-purple-50 border border-purple-100 rounded-lg p-3">
                <i class="fas fa-info-circle text-purple-600"></i>
                <span id="rrm-short-status" class="font-semibold text-purple-800">Return Request</span>
            </div>

            <div id="rrm-modal-content" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-hashtag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return ID</div>
                        <div class="text-gray-600" id="rrm-request-id">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-user text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">User</div>
                        <div class="text-gray-600" id="rrm-user">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-wrench text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Condition</div>
                        <div id="rrm-condition" class="inline-flex items-center gap-2"></div>

                    </div>
                </div>

                {{-- Condition breakdown + details --}}
                <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                    <div class="font-medium text-gray-800">Condition Breakdown</div>

                    <div id="rrm-breakdown" class="mt-2 text-sm text-gray-700">
                        <div id="rrm-breakdown-list" class="flex flex-wrap gap-2"></div>

                        <button id="rrm-breakdown-toggle" type="button" class="text-xs text-purple-600 mt-3 underline">
                            Show details
                        </button>

                        <div id="rrm-return-items-list-container" class="hidden mt-3">
                            <ul id="rrm-return-items-list" class="list-disc list-inside text-sm text-gray-700"></ul>
                        </div>
                    </div>
                </div>


                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-tag text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Status</div>
                        <div id="rrm-status-badge" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return Date</div>
                        <div class="text-gray-600" id="rrm-return-date">—</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-box-open text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Items</div>
                        <ul id="rrm-items" class="list-disc list-inside text-gray-600 mb-0"><li>—</li></ul>
                    </div>
                </div>

                <div class="col-span-2 hidden" id="rrm-damage-block">
                    <p class="text-red-600 font-semibold">⚠️ Damage / Remarks</p>
                    <p id="rrm-damage-reason" class="bg-red-50 text-red-800 p-2 rounded"></p>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200">
                <x-button variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'returnRequestModal')">
                    <i class="fa-solid fa-xmark mr-1"></i> Close
                </x-button>
            </div>
        </div>
    </x-modal>

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
            <x-button type="button" variant="secondary" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'confirmActionModal')">
                Cancel
            </x-button>

            <x-button type="button" id="confirmActionConfirmBtn" variant="danger" class="px-4 py-2 text-sm">
                Confirm
            </x-button>
        </div>
    </div>
</x-modal>

    <script>
        // JS-safe comments here
        window.LIST_ROUTE = "{{ route('return.requests.list') }}";
        window.PROCESS_BASE = "{{ url('/admin/return-requests') }}"; // matches previous PROCESS_BASE constant
        window.CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
