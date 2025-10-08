@props(['id' => 'dynamicModal', 'title' => 'Modal Title', 'maxWidth' => 'lg'])

@php
    // map width prop to tailwind max-w classes
    $widthMap = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
    ];
    $maxWidthClass = $widthMap[$maxWidth] ?? 'max-w-lg';
@endphp

<div
    id="{{ $id }}"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:px-0"
    aria-hidden="true"
>
    <div
        class="bg-white w-full sm:w-auto {{ $maxWidthClass }} mt-20 sm:mt-28 rounded-xl shadow-xl overflow-hidden relative flex flex-col transform transition-transform duration-300 ease-out"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        data-modal-dialog
    >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="{{ $id }}-title" class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: '{{ $id }}' }))"
                class="text-gray-400 hover:text-gray-600 transition"
                aria-label="Close modal"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div id="{{ $id }}Content" class="px-6 py-4 space-y-4 text-gray-700">
            {{ $slot }}
        </div>

        <!-- Footer (optional) -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button
                type="button"
                onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: '{{ $id }}' }))"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
            >
                Close
            </button>
        </div>
    </div>
</div>

{{-- one-time script to handle open/close for any dynamic-modal --}}
@once
<script>
    (function () {
        // listen for open/close globally
        window.addEventListener('open-modal', (e) => {
            const id = e?.detail;
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        });

        window.addEventListener('close-modal', (e) => {
            const id = e?.detail;
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        });

        // close when clicking outside dialog
        document.addEventListener('click', (e) => {
            const modal = e.target.closest('.fixed.inset-0');
            if (modal && e.target === modal) {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            }
        });

        // close with Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document
                    .querySelectorAll('.fixed.inset-0:not(.hidden)')
                    .forEach((modal) => {
                        modal.classList.add('hidden');
                        modal.setAttribute('aria-hidden', 'true');
                    });
                document.body.classList.remove('overflow-hidden');
            }
        });
    })();
</script>
@endonce
