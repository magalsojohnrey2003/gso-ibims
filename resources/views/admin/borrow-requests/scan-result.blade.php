<x-app-layout>
    <div class="py-10 px-6 max-w-2xl mx-auto">
        <div class="bg-white shadow-lg rounded-2xl p-8 text-center space-y-6">
            <div class="space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full {{ $updated ? 'bg-teal-100 text-teal-600' : 'bg-gray-100 text-gray-500' }}">
                    <i class="fas {{ $updated ? 'fa-qrcode' : 'fa-info-circle' }} text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">
                    QR Verification {{ $updated ? 'Complete' : 'Already Verified' }}
                </h1>
                <p class="text-gray-600">
                    {{ $message }}
                </p>
            </div>

            <div class="space-y-2 text-sm text-gray-600">
                @php
                    $statusLabel = $borrowRequest->status === 'qr_verified'
                        ? 'QR Verified'
                        : ucwords(str_replace('_', ' ', $borrowRequest->status ?? 'pending'));
                @endphp
                <div>
                    <span class="font-semibold text-gray-800">Request ID:</span>
                    <span class="ml-1 text-gray-700">#{{ $borrowRequest->id }}</span>
                </div>
                <div class="inline-flex items-center gap-2">
                    <span class="font-semibold text-gray-800">Current Status:</span>
                    <x-status-badge type="{{ $borrowRequest->status === 'qr_verified' ? 'qr' : 'info' }}" :text="$statusLabel" />
                </div>
            </div>

            <div class="pt-4">
                <x-button as="a" href="{{ route('borrow.requests') }}" variant="secondary" iconName="arrow-left">
                    Back to Borrow Requests
                </x-button>
            </div>
        </div>
    </div>
</x-app-layout>
