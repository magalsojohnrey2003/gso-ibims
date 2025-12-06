{{-- resources/views/admin/users/index.blade.php --}}
<x-app-layout>
    @php
        $noMainScroll = false; // Enable main content scrolling for consistency
    @endphp

    <!-- Title and Actions Section -->
    <div class="py-2">
        <div class="px-2">
            <!-- Title Row with Actions -->
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex-shrink-0 flex items-center">
            <x-title level="h2"
                                size="2xl"
                                weight="bold"
                                icon="users"
                                variant="s"
                iconStyle="plain"
                iconColor="title-purple"
                compact="true"> Manage Users </x-title>
                    </div>
                    
                    <!-- Actions -->
                        <div class="flex flex-wrap items-center justify-end gap-4">
                        <!-- Live Search Bar -->
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text"
                                   id="users-live-search"
                                placeholder="Search name or phone..."
                                   class="border border-gray-300 rounded-lg pl-12 pr-4 py-2.5 text-sm w-64 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all hover:border-gray-400" />
                        </div>
                        <!-- Sort Modal Trigger -->
                        <div class="flex items-center gap-3">
                                <button type="button"
                                    id="users-sort-trigger"
                                    aria-haspopup="dialog"
                                    aria-expanded="false"
                                    aria-controls="users-sort-modal"
                                    class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-purple-300 hover:text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500/40">
                                <i class="fas fa-sliders-h text-sm"></i>
                                <span id="users-sort-summary">Sort by</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="mb-4 alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 alert-error">{{ session('error') }}</div>
    @endif

    <!-- Table Section -->
    <style>
    #users-table {
        table-layout: fixed;
        width: 100%;
    }
    #users-table th,
    #users-table td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #users-table td[data-column="name"] {
        max-width: 14rem;
    }
    #users-table td[data-column="phone"] {
        max-width: 12rem;
    }
    #users-table td[data-column="status"] {
        max-width: 14rem;
    }
    #users-table th:last-child,
    #users-table td:last-child {
        min-width: 160px;
        width: 160px;
    }
    </style>
    <div class="pb-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
                <div class="table-container-no-scroll">
                    <table id="users-table" class="w-full text-sm text-center text-gray-600 gov-table">
                        <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                        <tr>
                            <th class="px-6 py-3 text-center">Name</th>
                            <th class="px-6 py-3 text-center">Phone #</th>
                            <th class="px-6 py-3 text-center">Created By</th>
                            <th class="px-6 py-3 text-center">Last Active</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody id="users-tbody" class="text-center">
                        <x-table-loading-state colspan="6" class="hidden" wire:loading.class.remove="hidden" />
                        @forelse($users as $user)
                            @include('admin.users._row', ['user' => $user])
                        @empty
                            <x-table-empty-state colspan="6" />
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

                <div class="p-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Registration QR Modal --}}
    <div id="registration-qr-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 p-4" role="dialog" aria-modal="true" aria-labelledby="registration-qr-title" aria-hidden="true">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl transform transition-all">
            <div class="bg-purple-600 text-white px-6 py-5 rounded-t-2xl relative">
                <button type="button" data-close-qr-modal class="absolute top-1/2 -translate-y-1/2 left-6 text-white hover:text-gray-200 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 id="registration-qr-title" class="text-2xl font-bold flex items-center gap-2 justify-center">
                    <i class="fas fa-qrcode"></i>
                    Registration QR Code
                </h3>
            </div>
            <div class="px-6 py-6 text-center">
                <p class="text-sm text-gray-500">Scan this code to open the restricted registration page.</p>
                <div id="registration-qr-output" class="mx-auto my-6 flex items-center justify-center"></div>
                <div class="text-xs sm:text-sm text-gray-600 break-all bg-gray-100 rounded-lg px-4 py-2">
                    <span class="font-semibold text-gray-700">Link:</span>
                    <a href="{{ url('/login') }}?action=register" class="text-purple-600 underline" target="_blank" rel="noopener">{{ url('/login') }}?action=register</a>
                </div>
                <div class="mt-6 flex flex-col sm:flex-row sm:justify-center gap-3">
                    <button type="button" id="registration-qr-copy" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 transition">
                        <i class="fas fa-link"></i>
                        <span class="text-sm font-semibold">Copy Link</span>
                    </button>
                    <button type="button" id="registration-qr-download" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 transition">
                        <i class="fas fa-download"></i>
                        <span class="text-sm font-semibold">Download QR</span>
                    </button>
                    <button type="button" data-close-qr-modal class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 transition">
                        <i class="fas fa-times"></i>
                        <span class="text-sm font-semibold">Close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal (hidden) --}}
    <div id="create-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl transform transition-all">
            <div class="bg-purple-600 text-white px-6 py-5 rounded-t-2xl relative">
                <button data-action="close-modal" class="absolute top-1/2 -translate-y-1/2 left-6 text-white hover:text-gray-200 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 class="text-2xl font-bold flex items-center gap-2 justify-center">
                    <i class="fas fa-plus"></i>
                    Create New User
                </h3>
            </div>
            <div class="px-6 py-6">
                @include('admin.users._form', [
                    'user' => null,
                    'action' => route('admin.users.store'),
                    'method' => 'POST',
                    'ajax' => true,
                ])
            </div>
        </div>
    </div>

    {{-- Edit Modal (content loaded via AJAX) --}}
    <div id="edit-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 p-4">
        <div id="edit-modal-content" class="bg-white rounded-2xl w-full max-w-3xl shadow-2xl transform transition-all">
            <div class="bg-purple-600 text-white px-6 py-5 rounded-t-2xl relative">
                <button data-action="close-modal" class="absolute top-1/2 -translate-y-1/2 left-6 text-white hover:text-gray-200 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 class="text-2xl font-bold flex items-center gap-2 justify-center">
                    <i class="fas fa-pencil-alt"></i>
                    Edit User
                </h3>
            </div>
            <div id="edit-form-container" class="px-6 py-6">
                <p class="text-sm text-gray-500">Loading...</p>
            </div>
        </div>
    </div>

    {{-- Archived Accounts Modal --}}
    <div id="archived-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl transform transition-all">
            <div class="bg-purple-600 text-white px-6 py-5 rounded-t-2xl relative">
                <button data-action="close-modal" class="absolute top-1/2 -translate-y-1/2 left-6 text-white hover:text-gray-200 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 class="text-2xl font-bold flex items-center gap-2 justify-center">
                    <i class="fas fa-box-archive"></i>
                    Archived Accounts
                </h3>
            </div>
            <div class="px-6 py-6">
                <div class="overflow-x-auto max-h-[60vh]">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Phone #</th>
                                <th class="px-6 py-3">Archived On</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archived-users-tbody">
                            @foreach($archivedUsers as $archived)
                                @include('admin.users._archived_row', ['user' => $archived])
                            @endforeach
                            <tr id="archived-empty-row" class="{{ $archivedUsers->isEmpty() ? '' : 'hidden' }}">
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="font-medium">No archived accounts</p>
                                        <p class="text-sm">Archived users will appear here for restoration.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Users Sort Modal --}}
    <div id="users-sort-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 p-4" role="dialog" aria-modal="true" aria-labelledby="users-sort-modal-title">
        <div data-sort-modal-panel class="w-full max-w-md rounded-2xl bg-white shadow-2xl" tabindex="-1">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <i class="fas fa-sliders-h text-purple-600"></i>
                    <h3 id="users-sort-modal-title" class="text-lg font-semibold text-gray-900">Sort &amp; Filter</h3>
                </div>
                <button type="button" class="rounded-full p-2 text-gray-500 hover:bg-gray-100 focus:outline-none" data-sort-close>
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Close</span>
                </button>
            </div>
            <div class="px-5 py-4 space-y-5 text-sm text-gray-700">
                <section>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Activity Range</p>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button type="button" class="sort-modal-option inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/40" data-sort-activity-option="week" aria-pressed="false">Week</button>
                        <button type="button" class="sort-modal-option inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/40" data-sort-activity-option="month" aria-pressed="false">Month</button>
                        <button type="button" class="sort-modal-option inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/40" data-sort-activity-option="year" aria-pressed="false">Year</button>
                    </div>
                </section>
                <section>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Borrowing Status</p>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button type="button" class="sort-modal-option inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/40" data-sort-status-option="good" aria-pressed="false">Good</button>
                        <button type="button" class="sort-modal-option inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/40" data-sort-status-option="fair" aria-pressed="false">Fair</button>
                        <button type="button" class="sort-modal-option inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/40" data-sort-status-option="risk" aria-pressed="false">Risk</button>
                    </div>
                </section>
            </div>
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-gray-200 bg-gray-50">
                <button type="button" id="users-sort-clear" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-400/40">
                    <i class="fas fa-undo"></i>
                    <span>Clear Filters</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-400/40" data-sort-close>
                        Cancel
                    </button>
                    <button type="button" id="users-sort-apply" class="inline-flex items-center gap-2 rounded-full bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400/60">
                        <i class="fas fa-check"></i>
                        <span>Apply</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Damage History Modal --}}
    <div id="damage-history-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 p-4">
        <div data-damage-modal-panel class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl transform transition-all">
            <div class="bg-purple-600 text-white px-4 py-3 rounded-t-2xl flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-base"></i>
                    <div class="leading-tight">
                        <h3 class="text-lg font-semibold tracking-tight">Items Condition</h3>
                        <p class="text-xs text-purple-100/90">Review recent incidents before approving requests.</p>
                    </div>
                </div>
                <button type="button" data-close-damage-modal class="rounded-full p-2 hover:bg-white/10 transition-colors focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="px-4 py-4 space-y-4 text-sm text-gray-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500">Accountability Summary</p>
                        <h4 id="damage-history-username" class="text-base font-semibold text-gray-900">Borrower</h4>
                        <p class="text-xs text-gray-500">Registered: <span id="damage-history-registered-date">&mdash;</span></p>
                    </div>
                    <span id="damage-history-status-badge" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                        <i class="fas fa-circle-check"></i>
                        <span>Good Standing</span>
                    </span>
                </div>

                <div id="damage-history-risk" class="hidden rounded-lg border px-3 py-2 text-xs flex items-start gap-2">
                    <i id="damage-history-risk-icon" class="fas fa-circle-exclamation mt-0.5"></i>
                    <span id="damage-history-risk-message">Borrower has recorded incidents. Review before approving new requests.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Recorded Incidents</p>
                        <p id="damage-history-count" class="mt-1 text-lg font-bold text-gray-900">0</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Last Incident</p>
                        <p id="damage-history-last" class="mt-1 text-sm font-semibold text-gray-700">None</p>
                    </div>
                </div>

                <div>
                    <h5 class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Incident Log</h5>
                    <div id="damage-history-loading" class="hidden mt-3 flex items-center justify-center gap-2 text-xs text-gray-500">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span>Loading items condition...</span>
                    </div>
                    <div id="damage-history-error" class="hidden mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>
                    <div id="damage-history-empty" class="hidden mt-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-4 text-xs text-gray-500 text-center">
                        No recorded incidents for this user.
                    </div>
                    <ul id="damage-history-list" class="mt-3 space-y-2 max-h-64 overflow-y-auto pr-1"></ul>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex justify-end">
                <x-button variant="secondary" class="px-3 py-2 text-sm" data-close-damage-modal>
                    Close
                </x-button>
            </div>

            <div id="damage-history-details-modal" class="hidden absolute inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm px-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-gray-200">
                        <div class="leading-tight">
                            <h4 id="damage-history-details-title" class="text-sm font-semibold text-gray-900">Request ID: --</h4>
                            <p id="damage-history-details-subtitle" class="text-xs text-gray-500">Date Reported: --</p>
                            <p class="text-[11px] text-gray-400 mt-1">Item Name | Status | Property Number</p>
                        </div>
                        <button type="button" data-close-details-modal class="rounded-full p-2 hover:bg-gray-100 focus:outline-none transition">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="damage-history-details-content" class="px-4 py-3 space-y-2 max-h-72 overflow-y-auto"></div>
                    <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2">
                        <x-button variant="secondary" class="px-3 py-2 text-sm" data-close-details-modal>Done</x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Action Menu --}}
    <div x-data="{ open: false }" class="fixed bottom-8 right-8 z-40">
        <button
            @click="open = !open"
            :aria-expanded="open"
            type="button"
            class="relative rounded-full w-14 h-14 flex items-center justify-center shadow-lg focus:outline-none transition-all duration-300 transform"
            :class="open ? 'bg-red-600 hover:bg-red-700 scale-105' : 'bg-purple-600 hover:bg-purple-700 hover:scale-110'">
            <span aria-hidden="true"
                class="absolute inset-0 rounded-full opacity-0 transition-opacity duration-300"
                :class="open ? 'opacity-20 bg-black/10' : ''"></span>
            <svg xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                stroke="currentColor"
                fill="none"
                class="w-7 h-7 text-white transform transition-transform duration-300"
                :class="open ? 'rotate-45' : 'rotate-0'">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
            <span class="sr-only">Open actions</span>
        </button>
        <div
            x-show="open"
            x-transition.origin.bottom.right
            class="absolute bottom-20 right-0 flex flex-col gap-3 items-end"
            @click.outside="open = false">
            <button
                data-open-create-user
                @click="open = false"
                class="group bg-white text-purple-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-purple-200 hover:border-purple-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
                <div class="bg-purple-100 p-2 rounded-lg group-hover:bg-purple-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                    </svg>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-sm">Create User</div>
                    <div class="text-xs text-purple-500">Add a new borrower account</div>
                </div>
            </button>
            <button
                data-open-registration-qr
                @click="open = false"
                class="group bg-white text-indigo-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-indigo-200 hover:border-indigo-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
                <div class="bg-indigo-100 p-2 rounded-lg group-hover:bg-indigo-200 transition-colors">
                    <i class="fas fa-qrcode text-lg"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-sm">Registration QR</div>
                    <div class="text-xs text-indigo-500">Share the sign-up link</div>
                </div>
            </button>
            <button
                data-open-archived-users
                @click="open = false"
                class="group bg-white text-amber-600 px-4 py-3 rounded-xl shadow-lg hover:shadow-xl border-2 border-amber-200 hover:border-amber-400 transition-all transform hover:scale-105 flex items-center space-x-3 min-w-[220px]">
                <div class="bg-amber-100 p-2 rounded-lg group-hover:bg-amber-200 transition-colors">
                    <i class="fas fa-archive text-lg"></i>
                </div>
                <div class="text-left">
                    <div class="font-semibold text-sm">Archived Accounts</div>
                    <div class="text-xs text-amber-500">Restore previously removed users</div>
                </div>
            </button>
        </div>
    </div>

    {{-- SweetAlert2 for toasts --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        // Toast configurations
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        // Show success toast
        function showSuccess(message) {
            Toast.fire({
                icon: 'success',
                title: message
            });
        }

        // Show error toast
        function showError(message) {
            Toast.fire({
                icon: 'error',
                title: message
            });
        }

        // Password toggle functionality (matching login-register.blade.php)
        function initPasswordToggles() {
            document.querySelectorAll('.password-eye').forEach(eye => {
                // Remove old listeners by cloning
                const newEye = eye.cloneNode(true);
                eye.parentNode.replaceChild(newEye, eye);
                
                newEye.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetSelector = this.getAttribute('data-target');
                    const input = document.querySelector(targetSelector);
                    const icon = this.querySelector('i');
                    
                    if (input && icon) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
                });
            });
        }

        // Initialize password toggles on page load
        initPasswordToggles();

        const registrationQrModal = document.getElementById('registration-qr-modal');
        const registrationQrContainer = document.getElementById('registration-qr-output');
        const registrationQrUrl = @json(url('/login').'?action=register');
        const registrationQrCopyButton = document.getElementById('registration-qr-copy');
        const registrationQrDownloadButton = document.getElementById('registration-qr-download');
        let registrationQrInstance = null;

        function openRegistrationQrModal() {
            if (!registrationQrModal) {
                return;
            }
            if (typeof QRCode === 'undefined') {
                console.error('QRCode library unavailable');
                if (typeof showError === 'function') {
                    showError('Unable to generate the QR code. Please try again later.');
                }
                return;
            }

            registrationQrModal.classList.remove('hidden');
            registrationQrModal.setAttribute('aria-hidden', 'false');

            if (registrationQrContainer) {
                registrationQrContainer.innerHTML = '';
                registrationQrInstance = new QRCode(registrationQrContainer, {
                    text: registrationQrUrl,
                    width: 240,
                    height: 240,
                    colorDark: '#1f2937',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            setTimeout(function() {
                const closeBtn = registrationQrModal.querySelector('[data-close-qr-modal]');
                if (closeBtn && typeof closeBtn.focus === 'function') {
                    try {
                        closeBtn.focus({ preventScroll: true });
                    } catch (err) {
                        closeBtn.focus();
                    }
                }
            }, 120);
        }

        function closeRegistrationQrModal() {
            if (!registrationQrModal) {
                return;
            }
            registrationQrModal.classList.add('hidden');
            registrationQrModal.setAttribute('aria-hidden', 'true');
            if (registrationQrContainer) {
                registrationQrContainer.innerHTML = '';
            }
            registrationQrInstance = null;
        }

        function copyRegistrationLinkFallback() {
            try {
                const tempInput = document.createElement('input');
                tempInput.type = 'text';
                tempInput.value = registrationQrUrl;
                tempInput.setAttribute('aria-hidden', 'true');
                tempInput.style.position = 'absolute';
                tempInput.style.left = '-9999px';
                document.body.appendChild(tempInput);
                tempInput.select();
                tempInput.setSelectionRange(0, tempInput.value.length);
                const copied = document.execCommand && document.execCommand('copy');
                document.body.removeChild(tempInput);

                if (copied && typeof showSuccess === 'function') {
                    showSuccess('Registration link copied to clipboard');
                } else if (typeof showError === 'function') {
                    showError('Copy failed. Please copy the link manually.');
                }
            } catch (err) {
                if (typeof showError === 'function') {
                    showError('Copy failed. Please copy the link manually.');
                }
            }
        }

        if (registrationQrCopyButton) {
            registrationQrCopyButton.addEventListener('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(registrationQrUrl)
                        .then(function() {
                            if (typeof showSuccess === 'function') {
                                showSuccess('Registration link copied to clipboard');
                            }
                        })
                        .catch(function() {
                            copyRegistrationLinkFallback();
                        });
                } else {
                    copyRegistrationLinkFallback();
                }
            });
        }

        if (registrationQrDownloadButton) {
            registrationQrDownloadButton.addEventListener('click', function() {
                if (!registrationQrContainer) {
                    return;
                }

                let dataUrl = '';
                const qrImage = registrationQrContainer.querySelector('img');
                const qrCanvas = registrationQrContainer.querySelector('canvas');

                if (qrImage && qrImage.currentSrc) {
                    dataUrl = qrImage.currentSrc;
                } else if (qrCanvas && qrCanvas.toDataURL) {
                    dataUrl = qrCanvas.toDataURL('image/png');
                }

                if (!dataUrl) {
                    if (typeof showError === 'function') {
                        showError('Unable to download the QR code. Please try again.');
                    }
                    return;
                }

                const link = document.createElement('a');
                link.href = dataUrl;
                link.download = 'registration-qr.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }

        // Simple modal handling
        document.addEventListener('click', function(e) {
            const openQr = e.target.closest('[data-open-registration-qr]');
            if (openQr) {
                e.preventDefault();
                openRegistrationQrModal();
                return;
            }

            const closeQr = e.target.closest('[data-close-qr-modal]');
            if (closeQr) {
                e.preventDefault();
                closeRegistrationQrModal();
                return;
            }

            if (registrationQrModal && !registrationQrModal.classList.contains('hidden') && e.target === registrationQrModal) {
                closeRegistrationQrModal();
                return;
            }

            const openCreate = e.target.closest('#open-create-modal, [data-open-create-user]');
            if (openCreate) {
                const modal = document.getElementById('create-modal');
                if (!modal) {
                    return;
                }

                const form = modal.querySelector('form');
                // Clear form inputs when opening create modal
                if (form) {
                    form.reset();
                    // Clear all validation states and input values
                    form.querySelectorAll('.user-form-field').forEach(field => {
                        field.classList.remove('error', 'success');
                        const input = field.querySelector('input');
                        if (input) {
                            if (input.name === 'phone') {
                                input.dataset.cleanValue = '';
                            }
                            input.value = '';
                            input.classList.remove('has-value');
                            input.removeAttribute('aria-invalid');
                        }
                        const errorDiv = field.querySelector('.error');
                        if (errorDiv) {
                            errorDiv.textContent = '';
                            errorDiv.setAttribute('aria-hidden', 'true');
                        }
                    });
                    clearFormErrors(form);
                    // Reset password field to password type
                    const pwdField = form.querySelector('#user-password-field');
                    if (pwdField) pwdField.type = 'password';
                    // Reset eye icon
                    const eyeIcon = form.querySelector('.password-eye i');
                    if (eyeIcon) {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                }
                modal.classList.remove('hidden');
                // Focus first input
                setTimeout(() => {
                    const firstInput = form?.querySelector('input[name="first_name"]');
                    if (firstInput && typeof firstInput.focus === 'function') {
                        firstInput.focus();
                    }
                }, 100);
            }

            const openArchived = e.target.closest('[data-open-archived-users]');
            if (openArchived) {
                const modal = document.getElementById('archived-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    toggleArchivedEmptyState();
                    setTimeout(() => {
                        const closeBtn = modal.querySelector('[data-action="close-modal"]');
                        if (closeBtn && typeof closeBtn.focus === 'function') {
                            closeBtn.focus();
                        }
                    }, 100);
                }
            }

            const close = e.target.closest('[data-action="close-modal"]');
            if (close) {
                // hide parent modal
                let modal = close.closest('#create-modal, #edit-modal');
                if (!modal) {
                    modal = close.closest('#archived-modal');
                }
                if (modal) modal.classList.add('hidden');
            }

            const openEdit = e.target.closest('.open-edit-modal');
            if (openEdit) {
                const url = openEdit.getAttribute('data-edit-url');
                const container = document.getElementById('edit-form-container');
                container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Loading...</p>';
                document.getElementById('edit-modal').classList.remove('hidden');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('HTTP error! status: ' + res.status);
                        }
                        return res.text();
                    })
                    .then(html => {
                        container.innerHTML = html;
                        // Re-initialize password toggles for edit form after a short delay
                        setTimeout(() => {
                            initPasswordToggles();
                            // Initialize validation for edit form
                            const editForm = container.querySelector('form');
                            if (editForm) {
                                initUserFormValidation(editForm);
                                initUserFormLockState(editForm);
                                const editTrigger = editForm.querySelector('[data-edit-form-trigger]');
                                const focusTarget = editTrigger || editForm.querySelector('input[name="first_name"]');
                                if (focusTarget && typeof focusTarget.focus === 'function') {
                                    try {
                                        focusTarget.focus({ preventScroll: true });
                                    } catch (err) {
                                        focusTarget.focus();
                                    }
                                }
                            }
                        }, 100);
                    })
                    .catch((error) => {
                        console.error('Error loading edit form:', error);
                        container.innerHTML = '<div class="text-center py-8"><p class="text-red-600 font-semibold mb-2">Failed to load form.</p><p class="text-sm text-gray-500">Please try again or refresh the page.</p></div>';
                    });
            }
        });

        // close modal on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const createModal = document.getElementById('create-modal');
                const editModal = document.getElementById('edit-modal');
                const archivedModal = document.getElementById('archived-modal');

                if (createModal) createModal.classList.add('hidden');
                if (editModal) editModal.classList.add('hidden');
                if (archivedModal) archivedModal.classList.add('hidden');
                closeRegistrationQrModal();
            }
        });

        // delegate close buttons inside forms
        document.addEventListener('click', function(e) {
            if (e.target && e.target.getAttribute('data-action') === 'close-modal') {
                const modal = e.target.closest('#create-modal, #edit-modal');
                if (modal) modal.classList.add('hidden');
            }
        });

        // AJAX form submission for create & edit
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form[data-ajax]');
            if (!form) return;
            e.preventDefault();

            const url = form.action;
            const formData = new FormData(form);
            const phoneInput = form.querySelector('input[name="phone"]');
            if (phoneInput) {
                const cleanPhone = (phoneInput.dataset && phoneInput.dataset.cleanValue)
                    ? phoneInput.dataset.cleanValue
                    : (phoneInput.value || '').toString().replace(/\D/g, '');
                formData.set('phone', cleanPhone);
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

            // Disable submit button and show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            }

            // send request
            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                console.log('User AJAX response:', data);
                if (data.errors) {
                    showFormErrors(form, data.errors);
                    showError('Please check the form for errors');
                } else if (data.success) {
                    // if create (no existing row) insert, else replace
                    const id = data.id;
                    const tbody = document.getElementById('users-tbody');
                    if (tbody) {
                        if (tbody.querySelector(`tr[data-user-id="${id}"]`)) {
                            // replace existing row
                            const existing = tbody.querySelector(`tr[data-user-id="${id}"]`);
                            existing.outerHTML = data.html;
                        } else {
                            // prepend new row
                            tbody.insertAdjacentHTML('afterbegin', data.html);
                        }
                    }
                    // close modal
                    const modal = form.closest('#create-modal, #edit-modal');
                    if (modal) modal.classList.add('hidden');
                    // clear any errors
                    clearFormErrors(form);
                    // clear form (reset inputs)
                    // Clear all validation states
                    form.querySelectorAll('.user-form-field').forEach(field => {
                        field.classList.remove('error', 'success');
                        const input = field.querySelector('input');
                        if (input) {
                            if (input.name === 'phone') {
                                input.dataset.cleanValue = '';
                            }
                            input.classList.remove('has-value');
                            input.removeAttribute('aria-invalid');
                        }
                        const errorDiv = field.querySelector('.error');
                        if (errorDiv) {
                            errorDiv.textContent = '';
                            errorDiv.setAttribute('aria-hidden', 'true');
                        }
                    });
                    form.reset();
                    // Reset password field visibility
                    const pwdField = form.querySelector('#user-password-field');
                    if (pwdField) pwdField.type = 'password';
                    // Reset eye icon
                    const eyeIcon = form.querySelector('.password-eye i');
                    if (eyeIcon) {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                    // show success message
                    showSuccess(data.message || 'User saved successfully');
                } else {
                    throw new Error(data.message || 'An unexpected error occurred');
                }
            }).catch(err => {
                console.error(err);
                showError(err.message || 'An unexpected error occurred');
            }).finally(() => {
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });

        // AJAX delete handling with modern single modal confirmation
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form.ajax-delete');
            if (!form) return;
            e.preventDefault();

            // Show modern single confirmation modal using SweetAlert2
            Swal.fire({
                title: 'Archive User?',
                text: "Archived users can be restored later from the Archived Accounts list.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Archive',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = form.action;
                    const formData = new FormData(form);

                    fetch(url, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                        credentials: 'same-origin'
                    }).then(async res => {
                        if (!res.ok) {
                            const body = await res.json().catch(() => ({}));
                            showError(body.message || 'Failed to delete user');
                            return;
                        }
                        const data = await res.json();
                        if (data.success) {
                            const tr = document.querySelector(`tr[data-user-id="${data.id}"]`);
                            if (tr) tr.remove();

                            if (data.archivedHtml) {
                                const archivedTbody = document.getElementById('archived-users-tbody');
                                if (archivedTbody) {
                                    archivedTbody.insertAdjacentHTML('afterbegin', data.archivedHtml);
                                }
                            }

                            toggleArchivedEmptyState();
                            showSuccess(data.message || 'User archived successfully');
                        }
                    }).catch(() => showError('Failed to delete user'));
                }
            });
        });

        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form.ajax-restore');
            if (!form) return;
            e.preventDefault();

            const url = form.action;
            const formData = new FormData(form);

            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
                credentials: 'same-origin'
            }).then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    showError(data.message || 'Failed to restore user');
                    return;
                }

                const archivedRow = document.querySelector(`tr[data-archived-user-id="${data.id}"]`);
                if (archivedRow) {
                    archivedRow.remove();
                }

                if (data.html) {
                    const tbody = document.getElementById('users-tbody');
                    if (tbody) {
                        if (tbody.querySelector(`tr[data-user-id="${data.id}"]`)) {
                            tbody.querySelector(`tr[data-user-id="${data.id}"]`).outerHTML = data.html;
                        } else {
                            tbody.insertAdjacentHTML('afterbegin', data.html);
                        }
                    }
                }

                toggleArchivedEmptyState();
                showSuccess(data.message || 'User restored successfully');
            }).catch(() => showError('Failed to restore user'));
        });

        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form.ajax-force-delete');
            if (!form) return;
            e.preventDefault();

            Swal.fire({
                title: 'Delete Permanently?',
                text: 'This will remove the user account and its data permanently. This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(result => {
                if (!result.isConfirmed) return;

                const url = form.action;
                const formData = new FormData(form);

                fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                    credentials: 'same-origin'
                }).then(async res => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        showError(data.message || 'Failed to delete user permanently');
                        return;
                    }

                    const archivedRow = document.querySelector(`tr[data-archived-user-id="${data.id}"]`);
                    if (archivedRow) {
                        archivedRow.remove();
                    }

                    toggleArchivedEmptyState();
                    showSuccess(data.message || 'User deleted permanently');
                }).catch(() => showError('Failed to delete user permanently'));
            });
        });

        function showFormErrors(form, errors) {
            const container = form.querySelector('#form-errors');
            if (!container) return;
            let html = '';
            for (const key in errors) {
                if (Array.isArray(errors[key])) {
                    errors[key].forEach(msg => html += `<div class="flex items-center gap-2"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg><span>${msg}</span></div>`);
                } else {
                    html += `<div class="flex items-center gap-2"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg><span>${errors[key]}</span></div>`;
                }
            }
            container.innerHTML = html;
            container.classList.remove('hidden');
        }

        function clearFormErrors(form) {
            const container = form.querySelector('#form-errors');
            if (container) {
                container.innerHTML = '';
                container.classList.add('hidden');
            }
        }

        function toggleArchivedEmptyState() {
            const tbody = document.getElementById('archived-users-tbody');
            const emptyRow = document.getElementById('archived-empty-row');
            if (!tbody || !emptyRow) return;
            const hasRows = tbody.querySelector('tr[data-archived-user-id]') !== null;
            emptyRow.classList.toggle('hidden', hasRows);
        }

        function formatPhoneForDisplay(value) {
            const digits = (value || '').toString().replace(/\D/g, '').slice(0, 11);
            if (!digits) return '';
            if (digits.length <= 4) return digits;
            if (digits.length <= 7) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
            return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
        }

        // Apply validation to user forms
        function initUserFormValidation(form) {
            if (!form) return;

            const inputs = form.querySelectorAll('input:not([type="hidden"])');
            inputs.forEach(input => {
                if (input.name === 'phone') {
                    const initial = input.getAttribute('data-initial-clean') || input.dataset.cleanValue || input.value;
                    const digits = (initial || '').toString().replace(/\D/g, '').slice(0, 11);
                    if (digits) {
                        input.dataset.cleanValue = digits;
                        input.value = formatPhoneForDisplay(digits);
                        input.classList.add('has-value');
                    }
                } else if (input.value && input.value.trim() !== '') {
                    input.classList.add('has-value');
                }

                // Input event listener
                input.addEventListener('input', function() {
                    if (this.name === 'phone') {
                        const digits = (this.value || '').toString().replace(/\D/g, '').slice(0, 11);
                        this.dataset.cleanValue = digits;
                        this.value = formatPhoneForDisplay(digits);
                        if (digits) {
                            this.classList.add('has-value');
                        } else {
                            this.classList.remove('has-value');
                        }
                    } else {
                        if (this.value && this.value.trim() !== '') {
                            this.classList.add('has-value');
                        } else {
                            this.classList.remove('has-value');
                        }
                    }

                    validateUserField(this);
                });

                // Blur validation
                input.addEventListener('blur', function() {
                    validateUserField(this);
                });
            });
        }

        function initUserFormLockState(form) {
            if (!form || !form.hasAttribute('data-edit-lockable-form')) return;

            const lockableInputs = form.querySelectorAll('[data-edit-lockable]');
            const trigger = form.querySelector('[data-edit-form-trigger]');
            const controls = form.querySelector('[data-edit-form-controls]');
            const cancelBtn = form.querySelector('[data-edit-form-cancel]');
            const submitBtn = form.querySelector('[data-edit-form-submit]');

            if (!lockableInputs.length || !trigger || !controls || !cancelBtn || !submitBtn) {
                return;
            }

            const setLocked = (locked) => {
                form.dataset.editLocked = locked ? 'true' : 'false';

                lockableInputs.forEach(input => {
                    const isSelect = input.tagName === 'SELECT';

                    if (isSelect) {
                        input.disabled = locked;
                        if (locked) {
                            input.setAttribute('aria-disabled', 'true');
                        } else {
                            input.removeAttribute('aria-disabled');
                        }
                    } else {
                        input.readOnly = locked;
                        if (locked) {
                            input.setAttribute('aria-readonly', 'true');
                        } else {
                            input.removeAttribute('aria-readonly');
                        }

                        if (!locked && input.name === 'password') {
                            input.value = '';
                            input.classList.remove('has-value');
                        }
                    }
                });

                if (locked) {
                    trigger.hidden = false;
                    trigger.classList.remove('hidden');
                    controls.hidden = true;
                    controls.classList.add('hidden');
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('aria-disabled', 'true');
                } else {
                    trigger.hidden = true;
                    trigger.classList.add('hidden');
                    controls.hidden = false;
                    controls.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.removeAttribute('aria-disabled');

                    requestAnimationFrame(() => {
                        const firstInput = lockableInputs[0];
                        if (firstInput) {
                            try {
                                firstInput.focus({ preventScroll: true });
                                if (typeof firstInput.select === 'function' && firstInput.type !== 'password') {
                                    firstInput.select();
                                }
                            } catch (err) {
                                try { firstInput.focus(); } catch (_) {}
                            }
                        }
                    });
                }
            };

            setLocked(true);

            trigger.addEventListener('click', function () {
                setLocked(false);
            });

            cancelBtn.addEventListener('click', function () {
                form.reset();

                lockableInputs.forEach(input => {
                    const isSelect = input.tagName === 'SELECT';

                    if (isSelect) {
                        input.classList.remove('has-value');
                    } else if (input.name === 'phone') {
                        const initialDigits = (input.getAttribute('data-initial-clean') || '').replace(/\D/g, '');
                        input.dataset.cleanValue = initialDigits;
                        input.value = formatPhoneForDisplay(initialDigits);
                        if (initialDigits) {
                            input.classList.add('has-value');
                        } else {
                            input.classList.remove('has-value');
                        }
                    } else if (input.type === 'password') {
                        input.value = '';
                        input.classList.remove('has-value');
                    } else {
                        if (input.value && input.value.trim() !== '') {
                            input.classList.add('has-value');
                        } else {
                            input.classList.remove('has-value');
                        }
                    }
                    input.removeAttribute('aria-invalid');
                });

                form.querySelectorAll('.user-form-field').forEach(field => {
                    field.classList.remove('error', 'success');
                    const errEl = field.querySelector('.error');
                    if (errEl) {
                        errEl.textContent = '';
                        errEl.setAttribute('aria-hidden', 'true');
                    }
                });

                const pwdField = form.querySelector('#user-password-field');
                if (pwdField) {
                    pwdField.type = 'password';
                }
                const pwdEye = form.querySelector('[data-target="#user-password-field"] i');
                if (pwdEye) {
                    pwdEye.classList.remove('fa-eye-slash');
                    pwdEye.classList.add('fa-eye');
                }

                clearFormErrors(form);

                setLocked(true);

                requestAnimationFrame(() => {
                    if (typeof trigger.focus === 'function') {
                        try {
                            trigger.focus({ preventScroll: true });
                        } catch (err) {
                            trigger.focus();
                        }
                    }
                });
            });
        }

        function validateUserField(input) {
            if (!input) return;
            const value = input.value ? input.value.trim() : '';
            const name = input.name;
            const fieldWrapper = input.closest('.user-form-field');

            if (!fieldWrapper) return;

            const errorDisplay = fieldWrapper.querySelector('.error');
            
            const setError = (message) => {
                if (errorDisplay) {
                    errorDisplay.textContent = message;
                    errorDisplay.setAttribute('aria-hidden', 'false');
                }
                fieldWrapper.classList.add('error');
                fieldWrapper.classList.remove('success');
                input.setAttribute('aria-invalid', 'true');
            };

            const setSuccess = () => {
                if (errorDisplay) {
                    errorDisplay.textContent = '';
                    errorDisplay.setAttribute('aria-hidden', 'true');
                }
                fieldWrapper.classList.remove('error');
                fieldWrapper.classList.add('success');
                input.removeAttribute('aria-invalid');
            };

            const clearState = () => {
                if (errorDisplay) {
                    errorDisplay.textContent = '';
                    errorDisplay.setAttribute('aria-hidden', 'true');
                }
                fieldWrapper.classList.remove('error', 'success');
                input.removeAttribute('aria-invalid');
            };

            // Validation logic
            switch(name) {
                case 'first_name':
                    if (!value) {
                        setError('First name is required');
                    } else if (!/^[A-Za-z\s-]+$/.test(value)) {
                        setError('Only letters, spaces, and - are allowed');
                    } else {
                        setSuccess();
                    }
                    break;

                case 'middle_name':
                    if (value && !/^[A-Za-z\s-]+$/.test(value)) {
                        setError('Only letters, spaces, and - are allowed');
                    } else if (value) {
                        setSuccess();
                    } else {
                        clearState();
                    }
                    break;

                case 'last_name':
                    if (!value) {
                        setError('Last name is required');
                    } else if (!/^[A-Za-z\s-]+$/.test(value)) {
                        setError('Only letters, spaces, and - are allowed');
                    } else {
                        setSuccess();
                    }
                    break;

                case 'phone': {
                    const digits = (input.dataset && input.dataset.cleanValue)
                        ? input.dataset.cleanValue
                        : value.replace(/\D/g, '');

                    if (!digits) {
                        setError('Phone number is required');
                    } else if (!/^\d{7,11}$/.test(digits)) {
                        setError('Phone must be 7–11 digits');
                    } else {
                        setSuccess();
                    }
                    break;
                }

                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!value) {
                        setError('Email is required');
                    } else if (!emailRegex.test(value)) {
                        setError('Provide a valid email address');
                    } else {
                        setSuccess();
                    }
                    break;

                case 'password':
                    const form = input.closest('form');
                    const isEditMode = form && form.querySelector('input[name="_method"]')?.value === 'PATCH';
                    
                    if (!isEditMode && !value) {
                        setError('Password is required');
                    } else if (value && value.length < 8) {
                        setError('Password must be at least 8 characters');
                    } else if (value) {
                        setSuccess();
                    } else {
                        clearState();
                    }
                    break;

                default:
                    if (value) setSuccess();
                    else clearState();
            }
        }

        // Initialize validation for create modal form on page load
        const createForm = document.querySelector('#create-modal form');
        if (createForm) {
            initUserFormValidation(createForm);
            initUserFormLockState(createForm);
        }

        toggleArchivedEmptyState();

        const borrowingStatusStyles = {
            good: { classes: 'bg-emerald-100 text-emerald-700', icon: 'fa-circle-check', label: 'Good' },
            fair: { classes: 'bg-amber-100 text-amber-700', icon: 'fa-circle-exclamation', label: 'Fair' },
            risk: { classes: 'bg-red-100 text-red-700', icon: 'fa-triangle-exclamation', label: 'Risk' },
        };

        const borrowingStatusAlerts = {
            fair: {
                classes: 'border-amber-200 bg-amber-50 text-amber-800',
                icon: 'fa-circle-exclamation',
                message: 'Borrower flagged for review. Assess recorded incidents before approving new requests.',
            },
            risk: {
                classes: 'border-red-200 bg-red-50 text-red-700',
                icon: 'fa-triangle-exclamation',
                message: 'Borrower marked At Risk. Coordinate with management before approving new requests.',
            },
        };

        const incidentReminderAlert = {
            classes: 'border-amber-200 bg-amber-50 text-amber-800',
            icon: 'fa-circle-exclamation',
            message: 'Borrower has recorded incidents. Review them before approving new requests.',
        };

        const riskBaseClasses = 'rounded-lg border px-3 py-2 text-xs flex items-start gap-2';

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDamageTimestamp(value) {
            if (!value) return '—';
            const normalized = value.replace(' ', 'T');
            const parsed = new Date(normalized);
            if (Number.isNaN(parsed.getTime())) {
                return value;
            }
            return parsed.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
        }

        function formatReportedDate(value) {
            if (!value) return '—';
            let source = value;
            if (value instanceof Date) {
                source = value.toISOString();
            }

            if (typeof source !== 'string') {
                return '—';
            }

            const normalized = source.replace(' ', 'T');
            const parsed = new Date(normalized);
            if (Number.isNaN(parsed.getTime())) {
                return source;
            }

            const month = parsed.toLocaleString(undefined, { month: 'short' });
            const day = parsed.toLocaleString(undefined, { day: 'numeric' });
            const year = parsed.getFullYear();
            return `${month}. ${day}, ${year}`;
        }

        function setupDamageHistoryModal() {
            const modal = document.getElementById('damage-history-modal');
            if (!modal) return;

            const loadingEl = document.getElementById('damage-history-loading');
            const errorEl = document.getElementById('damage-history-error');
            const emptyEl = document.getElementById('damage-history-empty');
            const listEl = document.getElementById('damage-history-list');
            const nameEl = document.getElementById('damage-history-username');
            const statusBadgeEl = document.getElementById('damage-history-status-badge');
            const countEl = document.getElementById('damage-history-count');
            const lastEl = document.getElementById('damage-history-last');
            const registeredDateEl = document.getElementById('damage-history-registered-date');
            const riskEl = document.getElementById('damage-history-risk');
            const riskIconEl = document.getElementById('damage-history-risk-icon');
            const riskMessageEl = document.getElementById('damage-history-risk-message');
            const closeButtons = modal.querySelectorAll('[data-close-damage-modal]');
            const detailModal = document.getElementById('damage-history-details-modal');
            const detailTitleEl = document.getElementById('damage-history-details-title');
            const detailSubtitleEl = document.getElementById('damage-history-details-subtitle');
            const detailContentEl = document.getElementById('damage-history-details-content');
            const detailCloseButtons = detailModal ? detailModal.querySelectorAll('[data-close-details-modal]') : [];
            const groupsCache = new Map();

            const closeDetailModal = () => {
                if (!detailModal) return;
                detailModal.classList.add('hidden');
                if (detailContentEl) {
                    detailContentEl.innerHTML = '';
                }
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                closeDetailModal();
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => closeModal());
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            const openDetailModal = (requestCode) => {
                if (!detailModal || !detailContentEl) {
                    return;
                }

                const group = groupsCache.get(requestCode);
                if (!group) {
                    return;
                }

                const { items, reportedAt } = group;
                if (detailTitleEl) {
                    detailTitleEl.textContent = `Request ID: ${group.requestCode}`;
                }
                if (detailSubtitleEl) {
                    const formattedDate = formatReportedDate(reportedAt);
                    detailSubtitleEl.textContent = `Date Reported: ${formattedDate}`;
                }

                detailContentEl.innerHTML = '';

                if (!items.length) {
                    const emptyMessage = document.createElement('p');
                    emptyMessage.className = 'text-sm text-gray-500';
                    emptyMessage.textContent = 'No incident records were found for this request.';
                    detailContentEl.appendChild(emptyMessage);
                } else {
                    const list = document.createElement('ul');
                    list.className = 'space-y-2';

                    items.forEach((item) => {
                        const entry = document.createElement('li');
                        entry.className = 'flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700';

                        const nameSpan = document.createElement('span');
                        nameSpan.className = 'font-medium text-gray-800 flex-1 min-w-[140px]';
                        nameSpan.textContent = item.itemName;

                        const conditionClass = conditionBadgeClasses[item.conditionKey] || 'bg-gray-100 text-gray-700';
                        const iconClass = conditionIconClasses[item.conditionKey] || 'fa-circle';
                        const safeLabel = escapeHtml(item.conditionLabel);

                        const statusWrapper = document.createElement('div');
                        statusWrapper.className = 'flex justify-center w-full sm:w-auto sm:flex-none sm:min-w-[110px]';

                        const statusSpan = document.createElement('span');
                        statusSpan.className = `inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full ${conditionClass}`;
                        statusSpan.innerHTML = `<i class='fas ${iconClass} text-[0.7rem]'></i><span>${safeLabel}</span>`;

                        statusWrapper.appendChild(statusSpan);

                        const propertySpan = document.createElement('span');
                        propertySpan.className = 'text-xs tracking-tight text-gray-600 flex-1 min-w-[140px] sm:text-right sm:text-[11px]';
                        propertySpan.textContent = item.propertyNumber || '—';

                        entry.appendChild(nameSpan);
                        entry.appendChild(statusWrapper);
                        entry.appendChild(propertySpan);
                        list.appendChild(entry);
                    });

                    detailContentEl.appendChild(list);
                }

                detailModal.classList.remove('hidden');
            };

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (detailModal && !detailModal.classList.contains('hidden')) {
                    closeDetailModal();
                    return;
                }

                if (!modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            if (detailModal) {
                detailCloseButtons.forEach((button) => button.addEventListener('click', closeDetailModal));
                detailModal.addEventListener('click', (event) => {
                    if (event.target === detailModal) {
                        closeDetailModal();
                    }
                });
            }

            const renderStatusBadge = (status, labelOverride) => {
                const normalized = status ? String(status).toLowerCase() : '';
                const styles = borrowingStatusStyles[normalized] || borrowingStatusStyles.good;
                const label = labelOverride || styles.label;
                statusBadgeEl.className = `inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full ${styles.classes}`;
                statusBadgeEl.innerHTML = `<i class="fas ${styles.icon}"></i><span>${label}</span>`;
            };

            const conditionBadgeClasses = {
                missing: 'bg-orange-100 text-orange-700',
                damage: 'bg-rose-100 text-rose-700',
                damaged: 'bg-rose-100 text-rose-700',
                minor_damage: 'bg-amber-100 text-amber-700',
            };

            const conditionIconClasses = {
                missing: 'fa-question-circle',
                damage: 'fa-exclamation-triangle',
                damaged: 'fa-exclamation-triangle',
                minor_damage: 'fa-exclamation-circle',
            };

            const renderIncidents = (incidents) => {
                listEl.innerHTML = '';
                groupsCache.clear();
                if (!Array.isArray(incidents) || incidents.length === 0) {
                    return;
                }

                const groups = new Map();

                incidents.forEach((incident) => {
                    const requestCode = incident.borrow_request_code
                        || (incident.borrow_request_id ? `#${incident.borrow_request_id}` : '—');
                    if (!groups.has(requestCode)) {
                        groups.set(requestCode, []);
                    }

                    groups.get(requestCode).push({
                        itemName: incident.item || 'Unknown Item',
                        propertyNumber: incident.property_number || '',
                        occurredAt: incident.occurred_at || null,
                        conditionKey: (incident.return_condition || '').toLowerCase(),
                        conditionLabel: incident.return_condition_label || 'Damage',
                    });
                });

                Array.from(groups.entries()).forEach(([requestCode, items]) => {
                    const reportedAt = items.reduce((earliest, current) => {
                        if (!current.occurredAt) {
                            return earliest;
                        }

                        const normalizedCurrent = String(current.occurredAt).replace(' ', 'T');
                        const currentDate = new Date(normalizedCurrent);
                        if (Number.isNaN(currentDate.getTime())) {
                            return earliest;
                        }

                        if (!earliest) {
                            return current.occurredAt;
                        }

                        const normalizedEarliest = String(earliest).replace(' ', 'T');
                        const earliestDate = new Date(normalizedEarliest);
                        if (Number.isNaN(earliestDate.getTime()) || currentDate < earliestDate) {
                            return current.occurredAt;
                        }

                        return earliest;
                    }, null);

                    groupsCache.set(requestCode, { requestCode, items, reportedAt });

                    const li = document.createElement('li');
                    li.className = 'rounded-lg border border-gray-200 bg-white shadow-sm px-3 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3';

                    const infoStack = document.createElement('div');
                    infoStack.className = 'flex items-center gap-2 text-sm font-semibold text-gray-900';

                    const indicator = document.createElement('span');
                    indicator.className = 'h-2 w-2 rounded-full bg-purple-500';

                    const summaryLabel = document.createElement('span');
                    const itemLabel = `${items.length} ${items.length === 1 ? 'Item' : 'Items'}`;
                    summaryLabel.textContent = `${requestCode} (${itemLabel})`;

                    infoStack.appendChild(indicator);
                    infoStack.appendChild(summaryLabel);

                    const historyButton = document.createElement('button');
                    historyButton.type = 'button';
                    historyButton.className = 'inline-flex items-center gap-2 rounded-lg border border-purple-200 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400/60 transition';
                    historyButton.innerHTML = '<i class="fas fa-clock-rotate-left text-sm"></i><span>History Log</span>';
                    historyButton.addEventListener('click', () => openDetailModal(requestCode));

                    li.appendChild(infoStack);
                    li.appendChild(historyButton);
                    listEl.appendChild(li);
                });
            };

            const applyRiskAlert = (statusKey, incidentCount) => {
                if (!riskEl) return;
                const normalized = statusKey ? String(statusKey).toLowerCase() : '';
                const alertMeta = borrowingStatusAlerts[normalized] || (incidentCount > 0 ? incidentReminderAlert : null);

                if (alertMeta) {
                    const composedClasses = `${riskBaseClasses} ${alertMeta.classes}`.trim();
                    riskEl.className = composedClasses;
                    if (riskIconEl) {
                        riskIconEl.className = `fas ${alertMeta.icon} mt-0.5`;
                    }
                    if (riskMessageEl) {
                        riskMessageEl.textContent = alertMeta.message;
                    }
                } else {
                    riskEl.className = `${riskBaseClasses} hidden`;
                    if (riskIconEl) {
                        riskIconEl.className = 'fas fa-circle-exclamation mt-0.5';
                    }
                    if (riskMessageEl) {
                        riskMessageEl.textContent = '';
                    }
                }
            };

            const handleDamageHistoryRequest = async (button) => {
                const url = button.getAttribute('data-damage-url');
                const userName = button.getAttribute('data-user-name') || 'Borrower';
                if (!url) {
                    return;
                }

                closeDetailModal();
                openModal();
                loadingEl.classList.remove('hidden');
                errorEl.classList.add('hidden');
                emptyEl.classList.add('hidden');
                listEl.innerHTML = '';
                nameEl.textContent = userName;
                if (registeredDateEl) {
                    const registeredAt = button.getAttribute('data-registered-at') || '—';
                    registeredDateEl.textContent = registeredAt;
                }
                applyRiskAlert(null, 0);

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        throw new Error('Failed to load damage history.');
                    }

                    const payload = await response.json();
                    const statusKey = String(payload.borrowing_status || 'good').toLowerCase();
                    renderStatusBadge(statusKey, payload.borrowing_status_label);

                    const incidents = Array.isArray(payload.incidents) ? payload.incidents : [];
                    const count = Number(payload.damage_count || incidents.length || 0);
                    countEl.textContent = count;

                    if (count > 0) {
                        renderIncidents(incidents);
                        emptyEl.classList.add('hidden');
                        const lastDate = incidents.length ? incidents[0].occurred_at : null;
                        lastEl.textContent = lastDate ? formatDamageTimestamp(lastDate) : 'Unknown';
                    } else {
                        emptyEl.classList.remove('hidden');
                        lastEl.textContent = 'None';
                    }

                    applyRiskAlert(statusKey, count);
                } catch (error) {
                    applyRiskAlert(null, 0);
                    errorEl.textContent = error?.message || 'Failed to load damage history.';
                    errorEl.classList.remove('hidden');
                    emptyEl.classList.add('hidden');
                    lastEl.textContent = 'Unknown';
                    if (registeredDateEl) {
                        registeredDateEl.textContent = button.getAttribute('data-registered-at') || '—';
                    }
                } finally {
                    loadingEl.classList.add('hidden');
                }
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('.damage-history-btn');
                if (!trigger) return;
                event.preventDefault();
                handleDamageHistoryRequest(trigger);
            });
        }
    </script>

    <script>
    // Live search and filtering controls for Manage Users
    document.addEventListener('DOMContentLoaded', function() {
        setupDamageHistoryModal();

        const searchInput = document.getElementById('users-live-search');
        const tableBody = document.getElementById('users-tbody');
        const sortTriggerButton = document.getElementById('users-sort-trigger');
        const sortSummaryLabel = document.getElementById('users-sort-summary');
        const sortModal = document.getElementById('users-sort-modal');
        const sortModalPanel = sortModal ? sortModal.querySelector('[data-sort-modal-panel]') : null;
        const sortApplyButton = document.getElementById('users-sort-apply');
        const sortClearButton = document.getElementById('users-sort-clear');
        const sortCloseButtons = sortModal ? sortModal.querySelectorAll('[data-sort-close]') : [];
        const activityOptionButtons = sortModal ? sortModal.querySelectorAll('[data-sort-activity-option]') : [];
        const statusOptionButtons = sortModal ? sortModal.querySelectorAll('[data-sort-status-option]') : [];
        const columnCount = document.querySelectorAll('#users-table thead th').length || 6;
        
        if (!tableBody) return;

        const filterWindows = {
            week: 7 * 24 * 60 * 60,
            month: 30 * 24 * 60 * 60,
            year: 365 * 24 * 60 * 60,
        };
        const activityLabels = {
            week: 'Week',
            month: 'Month',
            year: 'Year',
        };
        const statusLabels = {
            good: 'Good',
            fair: 'Fair',
            risk: 'Risk',
        };
        let activeLastActiveFilter = null;
        let activeStatusFilter = null;
        let pendingActivityFilter = null;
        let pendingStatusFilter = null;

        function setOptionActive(button, isActive) {
            button.classList.toggle('bg-purple-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-purple-600', isActive);
            button.classList.toggle('shadow-lg', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
            button.classList.toggle('border-gray-200', !isActive);
            button.classList.toggle('shadow-sm', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        }

        function updateSortModalSelections() {
            activityOptionButtons.forEach((button) => {
                const value = button.dataset.sortActivityOption || null;
                setOptionActive(button, value === pendingActivityFilter);
            });

            statusOptionButtons.forEach((button) => {
                const value = button.dataset.sortStatusOption || null;
                setOptionActive(button, value === pendingStatusFilter);
            });
        }

        function updateSortSummary() {
            if (!sortSummaryLabel || !sortTriggerButton) {
                return;
            }

            const summaryParts = [];
            if (activeLastActiveFilter && activityLabels[activeLastActiveFilter]) {
                summaryParts.push(activityLabels[activeLastActiveFilter]);
            }
            if (activeStatusFilter && statusLabels[activeStatusFilter]) {
                summaryParts.push(statusLabels[activeStatusFilter]);
            }

            const hasActive = summaryParts.length > 0;
            sortSummaryLabel.textContent = hasActive
                ? `Sort: ${summaryParts.join(' · ')}`
                : 'Sort by';

            sortTriggerButton.classList.toggle('bg-purple-50', hasActive);
            sortTriggerButton.classList.toggle('text-purple-700', hasActive);
            sortTriggerButton.classList.toggle('border-purple-300', hasActive);
            sortTriggerButton.classList.toggle('shadow-md', hasActive);
            sortTriggerButton.classList.toggle('bg-white', !hasActive);
            sortTriggerButton.classList.toggle('text-gray-700', !hasActive);
            sortTriggerButton.classList.toggle('border-gray-200', !hasActive);
            sortTriggerButton.classList.toggle('shadow-sm', !hasActive);
        }

        function openSortModal() {
            if (!sortModal) return;
            pendingActivityFilter = activeLastActiveFilter;
            pendingStatusFilter = activeStatusFilter;
            updateSortModalSelections();
            sortModal.classList.remove('hidden');
            if (sortTriggerButton) {
                sortTriggerButton.setAttribute('aria-expanded', 'true');
            }
            document.body.classList.add('overflow-hidden');
            setTimeout(() => {
                if (sortModalPanel && typeof sortModalPanel.focus === 'function') {
                    try {
                        sortModalPanel.focus({ preventScroll: true });
                    } catch (error) {
                        sortModalPanel.focus();
                    }
                }
            }, 80);
        }

        function closeSortModal() {
            if (!sortModal) return;
            sortModal.classList.add('hidden');
            if (sortTriggerButton) {
                sortTriggerButton.setAttribute('aria-expanded', 'false');
            }
            document.body.classList.remove('overflow-hidden');
            if (sortTriggerButton && typeof sortTriggerButton.focus === 'function') {
                try {
                    sortTriggerButton.focus({ preventScroll: true });
                } catch (error) {
                    sortTriggerButton.focus();
                }
            }
        }

        function passesLastActiveFilter(row) {
            if (!activeLastActiveFilter) return true;
            const windowSeconds = filterWindows[activeLastActiveFilter];
            if (!windowSeconds) return true;

            const lastActiveEl = row.querySelector('[data-last-active]');
            if (!lastActiveEl) return false;

            const timestamp = parseInt(lastActiveEl.getAttribute('data-last-active'), 10);
            if (!timestamp) return false;

            const now = Math.floor(Date.now() / 1000);
            return now - timestamp <= windowSeconds;
        }

        function passesStatusFilter(row) {
            if (!activeStatusFilter) return true;
            const status = row.getAttribute('data-borrowing-status');
            return status === activeStatusFilter;
        }
        
        function filterTable() {
            const searchTerm = (searchInput?.value || '').toLowerCase().trim();
            const digitsTerm = searchTerm.replace(/\D/g, '');
            const rows = tableBody.querySelectorAll('tr[data-user-id]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const nameCell = row.querySelector('td[data-column="name"]');
                const phoneCell = row.querySelector('td[data-column="phone"]');
                
                if (!nameCell || !phoneCell) return;
                
                const nameText = nameCell.textContent.toLowerCase();
                const phoneText = phoneCell.textContent.toLowerCase();
                const phoneDigits = (phoneCell.getAttribute('data-phone-digits') || '').toLowerCase();
                
                const matchesSearch = !searchTerm
                    || nameText.includes(searchTerm)
                    || phoneText.includes(searchTerm)
                    || (digitsTerm && phoneDigits.includes(digitsTerm));
                const matchesLastActive = passesLastActiveFilter(row);
                const matchesStatus = passesStatusFilter(row);

                const shouldShow = matchesSearch && matchesLastActive && matchesStatus;
                
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) {
                    visibleCount++;
                }
            });
            
            if (visibleCount === 0 && rows.length > 0) {
                let noResultsRow = document.getElementById('no-results-row-users');
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row-users';
                    noResultsRow.innerHTML = `
                        <td colspan="${columnCount}" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-medium">No users found</p>
                                <p class="text-sm">Try adjusting your search or filters</p>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(noResultsRow);
                }
            } else {
                const noResultsRow = document.getElementById('no-results-row-users');
                if (noResultsRow) {
                    noResultsRow.remove();
                }
            }

            updateSortSummary();
        }
        
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
            
            searchInput.addEventListener('focus', function() {
                this.placeholder = 'Type to Search';
            });
            
            searchInput.addEventListener('blur', function() {
                this.placeholder = 'Search name or phone...';
            });
        }

            if (sortTriggerButton) {
                sortTriggerButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    openSortModal();
                });
            }

            sortCloseButtons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    closeSortModal();
                });
            });

            if (sortModal) {
                sortModal.addEventListener('click', (event) => {
                    if (event.target === sortModal) {
                        closeSortModal();
                    }
                });
            }

            activityOptionButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const value = button.dataset.sortActivityOption || null;
                    pendingActivityFilter = pendingActivityFilter === value ? null : value;
                    updateSortModalSelections();
                });
            });

            statusOptionButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const value = button.dataset.sortStatusOption || null;
                    pendingStatusFilter = pendingStatusFilter === value ? null : value;
                    updateSortModalSelections();
                });
            });

            if (sortApplyButton) {
                sortApplyButton.addEventListener('click', () => {
                    activeLastActiveFilter = pendingActivityFilter;
                    activeStatusFilter = pendingStatusFilter;
                    filterTable();
                    closeSortModal();
                });
            }

            if (sortClearButton) {
                sortClearButton.addEventListener('click', () => {
                    pendingActivityFilter = null;
                    pendingStatusFilter = null;
                    activeLastActiveFilter = null;
                    activeStatusFilter = null;
                    updateSortModalSelections();
                    filterTable();
                    closeSortModal();
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && sortModal && !sortModal.classList.contains('hidden')) {
                    closeSortModal();
                }
            });

        // Initial filter state
        filterTable();
        
        // Live update "Last Active" times
        function updateLastActiveTimes() {
            const now = Math.floor(Date.now() / 1000); // Current Unix timestamp
            
            document.querySelectorAll('[data-last-active]').forEach(element => {
                const timestamp = parseInt(element.getAttribute('data-last-active'));
                const textSpan = element.querySelector('.last-active-text');
                const dotIndicator = element.querySelector('.fa-circle');
                
                if (!textSpan || !timestamp) return;
                
                const diffSeconds = now - timestamp;
                const diffMinutes = Math.floor(diffSeconds / 60);
                const diffHours = Math.floor(diffMinutes / 60);
                const diffDays = Math.floor(diffHours / 24);
                const diffWeeks = Math.floor(diffDays / 7);
                const diffMonths = Math.floor(diffDays / 30);
                const diffYears = Math.floor(diffDays / 365);
                
                let timeText = '';
                let colorClass = 'text-gray-400';
                
                if (diffSeconds < 60) {
                    timeText = 'Just now';
                    colorClass = 'text-green-500';
                } else if (diffMinutes < 60) {
                    timeText = diffMinutes === 1 ? '1 minute ago' : `${diffMinutes} minutes ago`;
                    colorClass = 'text-green-500';
                } else if (diffHours < 24) {
                    timeText = diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
                    colorClass = 'text-green-500';
                } else if (diffDays === 1) {
                    timeText = '1 day ago';
                    colorClass = 'text-yellow-500';
                } else if (diffDays < 7) {
                    timeText = `${diffDays} days ago`;
                    colorClass = 'text-yellow-500';
                } else if (diffWeeks === 1) {
                    timeText = '1 week ago';
                    colorClass = 'text-gray-400';
                } else if (diffWeeks < 4) {
                    timeText = `${diffWeeks} weeks ago`;
                    colorClass = 'text-gray-400';
                } else if (diffMonths === 1) {
                    timeText = '1 month ago';
                    colorClass = 'text-gray-400';
                } else if (diffMonths < 12) {
                    timeText = `${diffMonths} months ago`;
                    colorClass = 'text-gray-400';
                } else if (diffYears === 1) {
                    timeText = '1 year ago';
                    colorClass = 'text-gray-400';
                } else {
                    timeText = `${diffYears} years ago`;
                    colorClass = 'text-gray-400';
                }
                
                textSpan.textContent = timeText;
                
                // Update dot color indicator
                if (dotIndicator) {
                    dotIndicator.className = `fas fa-circle text-xs ${colorClass}`;
                }
            });
        }
        
        // Update immediately on page load
        updateLastActiveTimes();
        
        // Update every minute (60000ms)
        setInterval(updateLastActiveTimes, 60000);
    });
    </script>
    

    @vite('resources/js/app.js')
</x-app-layout>
