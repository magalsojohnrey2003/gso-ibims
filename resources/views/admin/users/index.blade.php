{{-- resources/views/admin/users/index.blade.php --}}
<x-app-layout>
    <div class="px-6 lg:px-8">
        <x-title
            level="h2"
            size="2xl"
            weight="bold"
            icon="users"
            variant="s"
            iconStyle="circle"
            iconBg="gov-accent"
            iconColor="white">
            Manage Users
        </x-title>
    </div>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search users..." class="border rounded px-3 py-2" />
                    <button class="btn btn-sm">Search</button>
                </form>

                <button id="open-create-modal" class="btn btn-primary">+ Create User</button>
            </div>

            @if(session('success'))
                <div class="mb-4 alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 alert-error">{{ session('error') }}</div>
            @endif

            <x-gov-table :headers="['Name','Email','Registered','']" tableId="users-table">
                @foreach($users as $user)
                    @include('admin.users._row', ['user' => $user])
                @endforeach
            </x-gov-table>
            <div class="p-4">{{ $users->links() }}</div>
        </div>
    </div>

    {{-- Create Modal (hidden) --}}
    <div id="create-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg w-full max-w-3xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Create User</h3>
                <button data-action="close-modal" class="text-gray-600">✕</button>
            </div>
            @include('admin.users._form', ['action' => route('admin.users.store'), 'method' => 'POST'])
        </div>
    </div>

    {{-- Edit Modal (content loaded via AJAX) --}}
    <div id="edit-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div id="edit-modal-content" class="bg-white rounded-lg w-full max-w-3xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Edit User</h3>
                <button data-action="close-modal" class="text-gray-600">✕</button>
            </div>
            <div id="edit-form-container">
                <p class="text-sm text-gray-500">Loading...</p>
            </div>
        </div>
    </div>

    {{-- SweetAlert2 for toasts --}}
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

        // Simple modal handling
        document.addEventListener('click', function(e) {
            const openCreate = e.target.closest('#open-create-modal');
            if (openCreate) {
                document.getElementById('create-modal').classList.remove('hidden');
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
                container.innerHTML = '<p class="text-sm text-gray-500">Loading...</p>';
                document.getElementById('edit-modal').classList.remove('hidden');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                    .then(res => res.text())
                    .then(html => container.innerHTML = html)
                    .catch(() => container.innerHTML = '<p class="text-red-600">Failed to load form.</p>');
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
                    // show success message
                    showSuccess(data.message || 'User saved successfully');
                    // clear form in case of create
                    if (!data.id) form.reset();
                } else {
                    throw new Error(data.message || 'An unexpected error occurred');
                }
            }).catch(err => {
                console.error(err);
                showError(err.message || 'An unexpected error occurred');
            });
        });

        // AJAX delete handling
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form.ajax-delete');
            if (!form) return;
            e.preventDefault();

            if (!confirm('Delete user?')) return;

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
                    alert(body.message || 'Failed to delete');
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    const tr = document.querySelector(`tr[data-user-id="${data.id}"]`);
                    if (tr) tr.remove();
                }
            }).catch(() => alert('Failed to delete'));
        });

        function showFormErrors(form, errors) {
            const container = form.querySelector('#form-errors');
            if (!container) return;
            let html = '';
            for (const key in errors) {
                if (Array.isArray(errors[key])) {
                    errors[key].forEach(msg => html += `<div>${msg}</div>`);
                } else {
                    html += `<div>${errors[key]}</div>`;
                }
            }
            container.innerHTML = html;
        }

        function clearFormErrors(form) {
            const container = form.querySelector('#form-errors');
            if (container) container.innerHTML = '';
        }
    </script>
    

    @vite('resources/js/app.js')
</x-app-layout>
