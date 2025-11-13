{{-- resources/views/admin/walk-in/index.blade.php --}}
<x-app-layout>
    <div class="py-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2" size="2xl" weight="bold" icon="clipboard-document-check" variant="s" iconStyle="plain" iconColor="gov-accent" compact="true"> Walk-in Requests </x-title>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text" id="walkin-live-search" placeholder="Search Borrower/Office" class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <a href="{{ route('admin.walkin.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 transition">
                            <i class="fas fa-plus-circle"></i> Create Walk-in Request
                        </a>
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
                            <th class="px-6 py-3 text-center">ID</th>
                            <th class="px-6 py-3 text-center">Borrower</th>
                            <th class="px-6 py-3 text-center">Borrow Date</th>
                            <th class="px-6 py-3 text-center">Return Date</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="walkinTableBody" class="text-center">
                        <tr>
                            <td colspan="6" class="py-4 text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <x-modal name="walkinDetailsModal" maxWidth="lg">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-3">
                    <i class="fa-solid fa-id-card text-purple-600"></i>
                    <span>Walk-in Details</span>
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition" @click="$dispatch('close-modal', 'walkinDetailsModal')">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div id="walkin-modal-content" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-user text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrower</div>
                        <div class="text-gray-600" data-field="borrower_name">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-building text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Office/Agency</div>
                        <div class="text-gray-600" data-field="office_agency">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-phone text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Contact</div>
                        <div class="text-gray-600" data-field="contact_number">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-map-marker-alt text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Address</div>
                        <div class="text-gray-600" data-field="address">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-day text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Borrow Schedule</div>
                        <div class="text-gray-600" data-field="borrowed_schedule">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Return Schedule</div>
                        <div class="text-gray-600" data-field="returned_schedule">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-scroll text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Purpose</div>
                        <div class="text-gray-600" data-field="purpose">—</div>
                    </div>
                </div>
                <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 sm:col-span-2">
                    <i class="fas fa-box-open text-purple-600 mt-1"></i>
                    <div>
                        <div class="font-medium text-gray-800">Items</div>
                        <ul class="list-disc list-inside text-gray-600" data-field="items">—</ul>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-2">
                <x-button variant="secondary" iconName="x-circle" class="px-4 py-2 text-sm" @click="$dispatch('close-modal', 'walkinDetailsModal')">Close</x-button>
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
                                <li>Loading...</li>
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

    <script>
        window.WALKIN_LIST_ROUTE = "{{ route('admin.walkin.list') }}";
        window.WALKIN_PRINT_ROUTE_TEMPLATE = "{{ route('admin.walkin.print', ['walkInRequest' => '__ID__']) }}";
        window.WALKIN_DELIVER_ROUTE_TEMPLATE = "{{ route('admin.walkin.deliver', ['id' => '__ID__']) }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @vite(['resources/js/admin-walk-in-index.js'])
</x-app-layout>
