<x-app-layout>
    @php $noMainScroll = false; @endphp

    <div class="py-2">
        <div class="px-2">
            <div id="userManpowerAlert" class="fixed top-4 right-4 space-y-2 z-[9999]"></div>
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-shrink-0 flex items-center">
                        <x-title level="h2" size="2xl" weight="bold" icon="users" variant="s" iconStyle="plain" iconColor="title-purple" compact="true"> Request Manpower </x-title>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text" id="user-manpower-search" placeholder="Search role, purpose, or request ID"
                                class="gov-input pl-12 pr-4 py-2.5 text-sm w-64 transition duration-200 focus:outline-none focus:ring-0" />
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
                                <th class="px-6 py-3 text-center">Borrowed Date</th>
                                <th class="px-6 py-3 text-center">Return Date</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userManpowerTableBody" class="text-center">
                            <x-table-loading-state colspan="5" />
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <template id="user-manpower-empty-state-template">
        <x-table-empty-state colspan="5" />
    </template>

    <!-- Create Modal -->
    <x-modal name="userManpowerCreateModal" maxWidth="3xl">
        <div class="relative h-full flex flex-col overflow-hidden" data-mp-wizard>
            <div class="sticky top-0 z-10 bg-purple-600 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-t-lg shadow">
                <div class="flex items-center gap-3">
                    <i class="fas fa-users text-2xl"></i>
                    <div>
                        <span class="text-xl font-bold block leading-tight">Request Manpower</span>
                        <span class="text-xs text-purple-100" id="manpowerWizardStepLabel">Step 1 of 3: Personnel Requirements &amp; Context</span>
                    </div>
                </div>
                <button class="text-white hover:text-gray-200 text-2xl self-start sm:self-auto" @click="$dispatch('close-modal','userManpowerCreateModal')"><i class="fas fa-times"></i></button>
            </div>

            <div class="flex-1 overflow-y-auto px-0">
                <form id="userManpowerForm" onsubmit="return false;" class="space-y-6 pt-4 pb-6">
                    <div class="px-6">
                        <ol id="manpowerWizardIndicator" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <li data-step-index="1" class="flex items-center gap-3 rounded-xl border border-purple-500 bg-purple-50 text-purple-700 shadow-sm px-4 py-3 text-sm">
                                <span data-step-badge class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white font-semibold">1</span>
                                <div>
                                    <p class="text-xs uppercase tracking-wide">Step 1</p>
                                    <p class="font-medium">Personnel &amp; Context</p>
                                </div>
                            </li>
                            <li data-step-index="2" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white text-gray-600 px-4 py-3 text-sm">
                                <span data-step-badge class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-700 font-semibold">2</span>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Step 2</p>
                                    <p class="font-medium text-gray-600">Location &amp; Schedule</p>
                                </div>
                            </li>
                            <li data-step-index="3" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white text-gray-600 px-4 py-3 text-sm">
                                <span data-step-badge class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-gray-700 font-semibold">3</span>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Step 3</p>
                                    <p class="font-medium text-gray-600">Documents &amp; Review</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div id="manpowerWizardSteps" class="space-y-6">
                        <section data-mp-step="1" class="px-6 space-y-5">
                            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-5">
                                <div class="flex items-center gap-2 text-purple-700">
                                    <i class="fas fa-user-friends text-lg"></i>
                                    <h3 class="text-lg font-semibold">Personnel Requirements &amp; Context</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Manpower quantity</label>
                                        <input type="number" min="1" max="999" id="mp_quantity" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200" oninput="if(this.value.length>3)this.value=this.value.slice(0,3);" placeholder="Max 999" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Manpower role</label>
                                        <select id="mp_role" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                            <option value="">Loading roles...</option>
                                        </select>
                                        <p id="mp_role_empty" class="text-xs text-red-500 mt-1 hidden">No roles available. Please contact the admin.</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Purpose</label>
                                        <input type="text" id="mp_purpose" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200" placeholder="Brief purpose of the request" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Request Office/Agency</label>
                                        <input type="text" id="mp_office" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200" placeholder="Optional" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section data-mp-step="2" class="hidden px-6 space-y-5">
                            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-5">
                                <div class="flex items-center gap-2 text-purple-700">
                                    <i class="fas fa-map-marked-alt text-lg"></i>
                                    <h3 class="text-lg font-semibold">Location &amp; Schedule</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Municipality / City</label>
                                        <select id="mp_municipality" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                            <option value="">Select municipality</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Barangay</label>
                                        <select id="mp_barangay" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200 disabled:bg-gray-100 disabled:cursor-not-allowed">
                                            <option value="">Select barangay</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Specific area (Purok/Zone/Sitio)</label>
                                        <input type="text" id="mp_location" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200" placeholder="e.g. Purok 3, Zone 2" />
                                    </div>
                                </div>
                                <div class="mt-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Location Preview</label>
                                    <div id="locationPreview" class="text-sm text-gray-700 bg-gray-100 rounded px-3 py-2">—</div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-4 border rounded-xl">
                                        <h4 class="font-semibold text-gray-900 text-sm mb-3">Start Schedule</h4>
                                        <label class="block text-xs font-semibold text-gray-600">Date</label>
                                        <input type="date" id="mp_start_date" class="gov-input mt-1 mb-3 block w-full rounded-md px-3 py-2 text-sm transition duration-200" />
                                        <label class="block text-xs font-semibold text-gray-600">Time (optional)</label>
                                        <input type="time" id="mp_start_time" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200" />
                                    </div>
                                    <div class="p-4 border rounded-xl">
                                        <h4 class="font-semibold text-gray-900 text-sm mb-3">End Schedule</h4>
                                        <label class="block text-xs font-semibold text-gray-600">Date</label>
                                        <input type="date" id="mp_end_date" class="gov-input mt-1 mb-3 block w-full rounded-md px-3 py-2 text-sm transition duration-200" />
                                        <label class="block text-xs font-semibold text-gray-600">Time (optional)</label>
                                        <input type="time" id="mp_end_time" class="gov-input mt-1 block w-full rounded-md px-3 py-2 text-sm transition duration-200" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Schedule Preview</label>
                                    <div id="schedulePreview" class="text-sm text-gray-700 bg-gray-100 rounded px-3 py-2">—</div>
                                </div>
                            </div>
                        </section>

                        <section data-mp-step="3" class="hidden px-6 space-y-5">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-4">
                                    <div class="flex items-center gap-2 text-purple-700">
                                        <i class="fas fa-file-upload text-lg"></i>
                                        <h3 class="text-lg font-semibold">Supporting Documents</h3>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Letter (PDF/JPG/PNG, max 5MB)</label>
                                        <input type="file" id="mp_letter" name="letter_file" accept="application/pdf,image/png,image/jpeg" class="filepond mt-1 block w-full text-sm" />
                                        <p class="text-xs text-gray-500 mt-1">Accepted: PDF, JPG, PNG. Max size: 5MB.</p>
                                    </div>
                                </div>

                                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-4">
                                    <div class="flex items-center gap-2 text-purple-700">
                                        <i class="fas fa-clipboard-check text-lg"></i>
                                        <h3 class="text-lg font-semibold">Review Details</h3>
                                    </div>
                                    <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                                        <div>
                                            <dt class="text-xs uppercase text-gray-500">Quantity &amp; Role</dt>
                                            <dd id="mpSummaryQuantity" class="mt-1 font-medium text-gray-900">—</dd>
                                            <dd id="mpSummaryRole" class="text-gray-600">—</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs uppercase text-gray-500">Office / Purpose</dt>
                                            <dd id="mpSummaryOffice" class="mt-1 font-medium text-gray-900">—</dd>
                                            <dd id="mpSummaryPurpose" class="text-gray-600">—</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs uppercase text-gray-500">Location</dt>
                                            <dd id="mpSummaryLocation" class="mt-1 font-medium text-gray-900">—</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs uppercase text-gray-500">Schedule</dt>
                                            <dd id="mpSummarySchedule" class="mt-1 font-medium text-gray-900">—</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs uppercase text-gray-500">Letter</dt>
                                            <dd id="mpSummaryLetter" class="mt-1 font-medium text-gray-900">No letter uploaded.</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </section>
                    </div>
                </form>
            </div>

            <div class="sticky bottom-0 left-0 right-0 z-10 bg-white border-t px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-b-lg shadow">
                <x-button type="button" variant="secondary" id="mpWizardPrev" class="hidden">Back</x-button>
                <div class="flex items-center gap-3 justify-end w-full sm:w-auto">
                    <x-button type="button" id="mpWizardNext" class="inline-flex items-center gap-2" iconName="arrow-right">Next</x-button>
                    <x-button type="button" id="saveManpowerRequest" variant="success" class="hidden inline-flex items-center gap-2" iconName="check-circle">Review &amp; Submit</x-button>
                </div>
            </div>

            <link href="https://unpkg.com/filepond@^4/dist/filepond.min.css" rel="stylesheet" />
            <script src="https://unpkg.com/filepond@^4/dist/filepond.min.js"></script>
            <script src="https://unpkg.com/filepond-plugin-file-validate-type@^1/dist/filepond-plugin-file-validate-type.min.js"></script>
            <script src="https://unpkg.com/filepond-plugin-file-validate-size@^2/dist/filepond-plugin-file-validate-size.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var today = new Date().toISOString().split('T')[0];
                document.getElementById('mp_start_date').setAttribute('min', today);
                document.getElementById('mp_end_date').setAttribute('min', today);

                if (window.FilePond) {
                    FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
                }

                function updateLocationPreview() {
                    var muni = document.getElementById('mp_municipality');
                    var brgy = document.getElementById('mp_barangay');
                    var area = document.getElementById('mp_location');
                    var txt = '';
                    if (muni && muni.value) txt += muni.options[muni.selectedIndex].text;
                    if (brgy && brgy.value) txt += (txt ? ', ' : '') + brgy.options[brgy.selectedIndex].text;
                    if (area && area.value) txt += (txt ? ', ' : '') + area.value;
                    document.getElementById('locationPreview').textContent = txt || '—';
                }
                document.getElementById('mp_municipality').addEventListener('change', updateLocationPreview);
                document.getElementById('mp_barangay').addEventListener('change', updateLocationPreview);
                document.getElementById('mp_location').addEventListener('input', updateLocationPreview);
                updateLocationPreview();

                function updateSchedulePreview() {
                    var sd = document.getElementById('mp_start_date').value;
                    var st = document.getElementById('mp_start_time').value;
                    var ed = document.getElementById('mp_end_date').value;
                    var et = document.getElementById('mp_end_time').value;
                    var txt = '';
                    if (sd) txt += 'Start: ' + sd + (st ? (' ' + st) : '');
                    if (ed) txt += (txt ? ' | ' : '') + 'End: ' + ed + (et ? (' ' + et) : '');
                    document.getElementById('schedulePreview').textContent = txt || '—';
                }
                document.getElementById('mp_start_date').addEventListener('change', updateSchedulePreview);
                document.getElementById('mp_start_time').addEventListener('input', updateSchedulePreview);
                document.getElementById('mp_end_date').addEventListener('change', updateSchedulePreview);
                document.getElementById('mp_end_time').addEventListener('input', updateSchedulePreview);
                updateSchedulePreview();
            });
            </script>
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

    <x-modal name="userManpowerViewModal" maxWidth="2xl" background="transparent">
        <div class="w-full max-h-[85vh] bg-[#4C1D95] dark:bg-gray-900 flex flex-col overflow-hidden rounded-2xl shadow-2xl">
            <div class="relative px-6 py-4 bg-[#4C1D95] text-white sticky top-0 z-30 flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold leading-snug flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        <span>My Manpower Request</span>
                    </h3>
                    <p class="text-sm text-purple-100 mt-1">Review approval status, schedule, and assigned personnel for this request.</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition"
                    @click="$dispatch('close-modal','userManpowerViewModal')"
                >
                    <span class="sr-only">Close modal</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900 px-6 py-5 space-y-5 text-sm text-gray-700 dark:text-gray-300">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-clipboard-list text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Request Summary</h4>
                        </div>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Request ID</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="id">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Role</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="role">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="status">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Qty (Approved / Requested)</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="quantity">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-calendar-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Schedule</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Borrow Date</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="borrow_date">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Return Date</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="return_date">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-map-marker-alt text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Location</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Municipality</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="municipality">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Barangay</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="barangay">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Specific Area</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="location">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2">
                        <div class="flex items-center gap-2 text-purple-700">
                            <i class="fas fa-bullseye text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">Purpose</h4>
                        </div>
                        <p class="mt-2 text-gray-600 dark:text-gray-300" data-user-view="purpose">—</p>
                    </div>

                    <div id="userManpowerRejectionCard" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2 hidden">
                        <div class="flex items-center gap-2 text-red-600">
                            <i class="fas fa-circle-xmark text-sm"></i>
                            <h4 class="text-sm font-semibold tracking-wide uppercase">Rejection Reason</h4>
                        </div>
                        <dl class="mt-3 space-y-3">
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Subject</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-white" data-user-view="rejection_subject">—</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-gray-500 dark:text-gray-400">Explanation</dt>
                                <dd class="mt-1 text-gray-600 dark:text-gray-300 whitespace-pre-line" data-user-view="rejection_detail">—</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 md:col-span-2 text-center">
                        <div class="flex items-center gap-2 justify-center text-purple-700">
                            <i class="fas fa-qrcode text-sm"></i>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wide uppercase">QR Status</h4>
                        </div>
                        <div id="userManpowerQr" class="mt-3 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg min-h-[160px]">
                            <div class="text-sm text-gray-400">QR code unavailable.</div>
                        </div>
                        <a data-user-view="public-url" href="#" target="_blank" class="text-indigo-600 hover:underline text-sm mt-3 inline-flex items-center justify-center gap-1 hidden">
                            <i class="fas fa-external-link-alt text-xs"></i>
                            <span>Open status page</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end">
                <x-button variant="secondary" @click="$dispatch('close-modal','userManpowerViewModal')">Close</x-button>
            </div>
        </div>
    </x-modal>

    <div id="userManpowerBadgeTemplates" class="hidden">
        <template data-status="pending"><x-status-badge type="pending" text="Pending" /></template>
        <template data-status="validated"><x-status-badge type="info" text="Validated" /></template>
        <template data-status="approved"><x-status-badge type="accepted" text="Approved" /></template>
        <template data-status="rejected"><x-status-badge type="rejected" text="Rejected" /></template>
        <template data-status="default"><x-status-badge type="gray" text="—" /></template>
    </div>

    <script>
        window.USER_MANPOWER = {
            list: "{{ route('user.manpower.list') }}",
            store: "{{ route('user.manpower.store') }}",
            print: "{{ route('user.manpower.print', '__ID__') }}",
            roles: "{{ route('manpower.roles.index') }}",
            locations: {
                municipalities: "{{ route('api.locations.municipalities') }}",
                barangays: "{{ url('api/locations/barangays') }}",
            },
            csrf: "{{ csrf_token() }}",
        };
        window.renderUserManpowerBadge = function(status){
            const tpl = document.querySelector(`#userManpowerBadgeTemplates template[data-status="${(status||'').toLowerCase()}"]`) || document.querySelector('#userManpowerBadgeTemplates template[data-status="default"]');
            return tpl ? tpl.innerHTML : status;
        };
    </script>

    @vite(['resources/js/app.js'])
</x-app-layout>
