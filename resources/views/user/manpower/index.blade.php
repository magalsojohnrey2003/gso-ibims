<x-app-layout>
    @php $noMainScroll = false; @endphp

    <div class="py-2">
        <div class="px-2">
            <div id="userManpowerAlert" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2" size="2xl" weight="bold" icon="users" variant="s" iconStyle="plain" iconColor="gov-accent" compact="true"> Request Manpower </x-title>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text" id="user-manpower-search" placeholder="Search role or purpose"
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <x-button id="openManpowerCreate" variant="primary" iconName="plus" class="px-4 py-2 text-sm">Add Request</x-button>
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
                                <th class="px-6 py-3 text-center">Request ID</th>
                                <th class="px-6 py-3 text-center">Qty (Approved / Requested)</th>
                                <th class="px-6 py-3 text-center">Role</th>
                                <th class="px-6 py-3 text-center">Schedule</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userManpowerTableBody" class="text-center">
                            <tr><td colspan="6" class="py-4 text-gray-500">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Wizard Modal -->
    <x-modal name="userManpowerCreateModal" maxWidth="2xl">
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Request Manpower</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','userManpowerCreateModal')"><i class="fas fa-times"></i></button>
            </div>

            <div class="border rounded-lg overflow-hidden" data-accordion-group>
                <!-- Step 1 -->
                <button type="button" class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 text-left" data-accordion-trigger aria-expanded="true">
                    <span class="font-semibold text-gray-800">Step 1: Details</span>
                    <i class="fas fa-chevron-down text-gray-500" data-accordion-caret></i>
                </button>
                <div class="px-4 py-4" data-accordion-panel data-accordion-open>
                    <form id="userManpowerForm" onsubmit="return false;" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Manpower quantity</label>
                            <input type="number" min="1" id="mp_quantity" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Manpower role</label>
                            <select id="mp_role" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                <option value="">Loading roles...</option>
                            </select>
                            <p id="mp_role_empty" class="text-xs text-red-500 mt-1 hidden">No roles available. Please contact the admin.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Purpose</label>
                            <input type="text" id="mp_purpose" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Brief purpose of the request" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" id="mp_location" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Request Office/Agency</label>
                            <input type="text" id="mp_office" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Optional" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date & Time</label>
                            <input type="datetime-local" id="mp_start" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date & Time</label>
                            <input type="datetime-local" id="mp_end" class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-purple-500" />
                        </div>
                    </form>
                    <div class="flex justify-end mt-3">
                        <x-button id="goToStep2" variant="primary">Next</x-button>
                    </div>
                </div>

                <!-- Step 2 -->
                <button type="button" class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 text-left" data-accordion-trigger aria-expanded="false">
                    <span class="font-semibold text-gray-800">Step 2: Upload Letter</span>
                    <i class="fas fa-chevron-down text-gray-500" data-accordion-caret></i>
                </button>
                <div class="px-4 py-4" data-accordion-panel>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Letter (PDF/JPG/PNG, max 5MB)</label>
                            <input type="file" id="mp_letter" accept="application/pdf,image/*" class="mt-1 block w-full text-sm" />
                        </div>
                        <div id="mp_letter_preview" class="hidden border rounded p-3 text-sm text-gray-600"></div>
                    </div>
                    <div class="flex justify-between mt-3">
                        <x-button id="backToStep1" variant="secondary">Back</x-button>
                        <x-button id="saveManpowerRequest" variant="success">Save</x-button>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

    <!-- Confirmation Modal -->
    <x-modal name="userManpowerConfirmModal" maxWidth="sm">
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Submit Manpower Request?</h3>
            <p class="text-sm text-gray-600">Please confirm you want to submit this request.</p>
            <div class="flex justify-end gap-3">
                <x-button variant="secondary" @click="$dispatch('close-modal','userManpowerConfirmModal')">Cancel</x-button>
                <x-button id="confirmManpowerSubmit" variant="primary">Submit</x-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="userManpowerViewModal" maxWidth="2xl">
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Request Details</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="$dispatch('close-modal','userManpowerViewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div>
                    <div class="text-gray-500 uppercase text-xs">Request ID</div>
                    <div class="font-semibold text-gray-900" data-user-view="id">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Status</div>
                    <div class="font-semibold text-gray-900" data-user-view="status">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Role</div>
                    <div class="font-semibold text-gray-900" data-user-view="role">—</div>
                </div>
                <div>
                    <div class="text-gray-500 uppercase text-xs">Qty (Approved / Requested)</div>
                    <div class="font-semibold text-gray-900" data-user-view="quantity">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Schedule</div>
                    <div class="font-semibold text-gray-900" data-user-view="schedule">—</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500 uppercase text-xs">Purpose</div>
                    <div class="font-medium text-gray-900" data-user-view="purpose">—</div>
                </div>
            </div>
            <div class="border-t pt-4 flex flex-col md:flex-row gap-6">
                <div class="flex-1">
                    <div class="text-sm text-gray-500 uppercase mb-2">Letter</div>
                    <div data-user-view="letter" class="text-sm text-gray-700">No letter uploaded.</div>
                </div>
                <div class="flex-1 text-center">
                    <div class="text-sm text-gray-500 uppercase mb-2">QR Status</div>
                    <div id="userManpowerQr" class="flex items-center justify-center" style="min-height: 160px;">
                        <div class="text-sm text-gray-400">QR code unavailable.</div>
                    </div>
                    <a data-user-view="public-url" href="#" target="_blank" class="text-indigo-600 hover:underline text-sm mt-2 hidden">Open status page</a>
                </div>
            </div>
        </div>
    </x-modal>

    <div id="userManpowerBadgeTemplates" class="hidden">
        <template data-status="pending"><x-status-badge type="pending" text="Pending" /></template>
        <template data-status="approved"><x-status-badge type="accepted" text="Approved" /></template>
        <template data-status="rejected"><x-status-badge type="rejected" text="Rejected" /></template>
        <template data-status="default"><x-status-badge type="gray" text="—" /></template>
    </div>

    <script>
        window.USER_MANPOWER = {
            list: "{{ route('user.manpower.list') }}",
            store: "{{ route('user.manpower.store') }}",
            roles: "{{ route('manpower.roles.index') }}",
            csrf: "{{ csrf_token() }}",
        };
        window.renderUserManpowerBadge = function(status){
            const tpl = document.querySelector(`#userManpowerBadgeTemplates template[data-status="${(status||'').toLowerCase()}"]`) || document.querySelector('#userManpowerBadgeTemplates template[data-status="default"]');
            return tpl ? tpl.innerHTML : status;
        };
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
