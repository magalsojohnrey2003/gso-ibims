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
                        <!-- Last Active Filter -->
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-gray-600">Sort by:</span>
                            <div class="inline-flex items-center gap-2 bg-gray-100 rounded-full p-1 border border-gray-200">
                                <button type="button"
                                        class="last-active-filter-btn px-3 py-1 text-xs font-semibold rounded-full text-gray-600 transition-all"
                                        data-last-active-filter="week">
                                    Week
                                </button>
                                <button type="button"
                                        class="last-active-filter-btn px-3 py-1 text-xs font-semibold rounded-full text-gray-600 transition-all"
                                        data-last-active-filter="month">
                                    Month
                                </button>
                                <button type="button"
                                        class="last-active-filter-btn px-3 py-1 text-xs font-semibold rounded-full text-gray-600 transition-all"
                                        data-last-active-filter="year">
                                    Year
                                </button>
                            </div>
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
    #users-table td[data-column="created"] {
        max-width: 10rem;
    }
    #users-table th:last-child,
    #users-table td:last-child {
        min-width: 112px;
        width: 112px;
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
                            <th class="px-6 py-3 text-center">Registered</th>
                            <th class="px-6 py-3 text-center">Last Active</th>
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
                    if (input.name === 'phone') {
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
    </script>

    <script>
    // Live search and "Last Active" filtering for Manage Users
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('users-live-search');
        const tableBody = document.getElementById('users-tbody');
        const filterButtons = document.querySelectorAll('[data-last-active-filter]');
        const columnCount = document.querySelectorAll('#users-table thead th').length || 6;
        
        if (!tableBody) return;

        const filterWindows = {
            week: 7 * 24 * 60 * 60,
            month: 30 * 24 * 60 * 60,
            year: 365 * 24 * 60 * 60,
        };
        let activeLastActiveFilter = null;

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

                const shouldShow = matchesSearch && matchesLastActive;
                
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

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.lastActiveFilter;
                const isActive = activeLastActiveFilter === filter;
                
                activeLastActiveFilter = isActive ? null : filter;

                filterButtons.forEach(btn => {
                    btn.classList.remove('bg-purple-600', 'text-white', 'shadow');
                    btn.setAttribute('aria-pressed', 'false');
                });

                if (!isActive) {
                    button.classList.add('bg-purple-600', 'text-white', 'shadow');
                    button.setAttribute('aria-pressed', 'true');
                }

                filterTable();
            });
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
