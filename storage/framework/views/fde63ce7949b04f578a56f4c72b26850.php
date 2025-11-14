
<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php
        $noMainScroll = false; // Enable main content scrolling for consistency
    ?>

    <!-- Title and Actions Section -->
    <div class="py-2">
        <div class="px-2">
            <!-- Title Row with Actions -->
            <div class="rounded-2xl shadow-lg bg-white border border-gray-200 px-6 py-4 mb-2">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex-shrink-0 flex items-center">
            <?php if (isset($component)) { $__componentOriginala29c4b6de1220dbc50317dc759b47929 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala29c4b6de1220dbc50317dc759b47929 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.title','data' => ['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'users','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('title'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['level' => 'h2','size' => '2xl','weight' => 'bold','icon' => 'users','variant' => 's','iconStyle' => 'plain','iconColor' => 'gov-accent','compact' => 'true']); ?> Manage Users  <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala29c4b6de1220dbc50317dc759b47929)): ?>
<?php $attributes = $__attributesOriginala29c4b6de1220dbc50317dc759b47929; ?>
<?php unset($__attributesOriginala29c4b6de1220dbc50317dc759b47929); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala29c4b6de1220dbc50317dc759b47929)): ?>
<?php $component = $__componentOriginala29c4b6de1220dbc50317dc759b47929; ?>
<?php unset($__componentOriginala29c4b6de1220dbc50317dc759b47929); ?>
<?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex flex-wrap items-center justify-end gap-4">
                        <!-- Live Search Bar -->
                        <div class="flex-shrink-0 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input type="text"
                                   id="users-live-search"
                                   placeholder="Search users..."
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
                        <button id="open-create-modal" class="btn btn-primary">+ Create User</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if(session('success')): ?>
        <div class="mb-4 alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="mb-4 alert-error"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <!-- Table Section -->
    <div class="pb-2">
        <div class="px-2">
            <div class="rounded-2xl shadow-lg border border-gray-200 table-wrapper">
                <div class="table-container-no-scroll">
                    <table id="users-table" class="w-full text-sm text-center text-gray-600 gov-table">
                        <thead class="bg-purple-600 text-white text-xs uppercase font-semibold text-center">
                        <tr>
                            <th class="px-6 py-3 text-center">Name</th>
                            <th class="px-6 py-3 text-center">Email</th>
                            <th class="px-6 py-3 text-center">Created By</th>
                            <th class="px-6 py-3 text-center">Registered</th>
                            <th class="px-6 py-3 text-center">Last Active</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody id="users-tbody" class="text-center">
                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('admin.users._row', ['user' => $user], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

                <div class="p-4">
                    <?php echo e($users->links()); ?>

                </div>
            </div>
        </div>
    </div>

    
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
                <?php echo $__env->make('admin.users._form', ['action' => route('admin.users.store'), 'method' => 'POST'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>

    
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

    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        // Simple modal handling
        document.addEventListener('click', function(e) {
            const openCreate = e.target.closest('#open-create-modal');
            if (openCreate) {
                const modal = document.getElementById('create-modal');
                const form = modal.querySelector('form');
                // Clear form inputs when opening create modal
                if (form) {
                    form.reset();
                    // Clear all validation states
                    form.querySelectorAll('.user-form-field').forEach(field => {
                        field.classList.remove('error', 'success');
                        const input = field.querySelector('input');
                        if (input) {
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
                    if (firstInput) firstInput.focus();
                }, 100);
            }

            const close = e.target.closest('[data-action="close-modal"]');
            if (close) {
                // hide parent modal
                let modal = close.closest('#create-modal, #edit-modal');
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
                document.getElementById('create-modal').classList.add('hidden');
                document.getElementById('edit-modal').classList.add('hidden');
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
                title: 'Delete User?',
                text: "This action cannot be undone. Are you sure you want to delete this user?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete',
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
                            showSuccess('User deleted successfully');
                        }
                    }).catch(() => showError('Failed to delete user'));
                }
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

        // Apply validation to user forms
        function initUserFormValidation(form) {
            if (!form) return;

            const inputs = form.querySelectorAll('input:not([type="hidden"])');
            inputs.forEach(input => {
                // Add has-value class if input has value
                if (input.value && input.value.trim() !== '') {
                    input.classList.add('has-value');
                }

                // Input event listener
                input.addEventListener('input', function() {
                    if (this.value && this.value.trim() !== '') {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                    validateUserField(this);
                });

                // Blur validation
                input.addEventListener('blur', function() {
                    validateUserField(this);
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
        }
            }
        }
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
            const rows = tableBody.querySelectorAll('tr[data-user-id]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const nameCell = row.querySelector('td:nth-child(1)');
                const emailCell = row.querySelector('td:nth-child(2)');
                
                if (!nameCell || !emailCell) return;
                
                const nameText = nameCell.textContent.toLowerCase();
                const emailText = emailCell.textContent.toLowerCase();
                
                const matchesSearch = !searchTerm || nameText.includes(searchTerm) || emailText.includes(searchTerm);
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
                this.placeholder = 'Search users...';
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
    

    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/admin/users/index.blade.php ENDPATH**/ ?>