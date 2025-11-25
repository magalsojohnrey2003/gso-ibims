@php
    $requestor = optional($manpowerRequest->user)->full_name
        ?: optional($manpowerRequest->user)->name
        ?: 'Requestor information unavailable';
    $statusValue = $manpowerRequest->status ?? 'pending';
    $statusLabel = ucwords(str_replace('_', ' ', $statusValue));
    $badgeType = match ($statusValue) {
        'approved' => 'accepted',
        'rejected' => 'rejected',
        'pending' => 'pending',
        default => 'info',
    };
    $requestCode = $manpowerRequest->formatted_request_id ?? ($manpowerRequest->id ? sprintf('MP-%04d', $manpowerRequest->id) : null);
@endphp

<x-app-layout>
    <div class="py-10 px-6 bg-teal-50 min-h-screen">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow-lg rounded-2xl p-8 text-center space-y-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-teal-100 text-teal-600">
                        <i class="fas {{ $updated ? 'fa-qrcode' : 'fa-info-circle' }} text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        QR Verification {{ $updated ? 'Complete' : 'Already Processed' }}
                    </h1>
                    <p class="text-gray-600">
                        {{ $message }}
                    </p>
                </div>

                <div class="space-y-2 text-sm text-gray-600">
                    <div>
                        <span class="font-semibold text-gray-800">Request ID:</span>
                        <span class="ml-1 text-gray-700">{{ $requestCode ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-800">Borrower:</span>
                        <span class="ml-1 text-gray-700">{{ $requestor }}</span>
                    </div>
                    <div class="inline-flex items-center gap-2">
                        <span class="font-semibold text-gray-800">Current Status:</span>
                        <x-status-badge :type="$badgeType" :text="$statusLabel" />
                    </div>
                    @if(isset($scanTimestamp))
                        <div>
                            <span class="font-semibold text-gray-800">Scan Timestamp:</span>
                            <span class="ml-1 text-gray-700">{{ $scanTimestamp->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                        </div>
                    @endif
                </div>

                @if($downloadUrl)
                    <div class="pt-2">
                        <x-button as="a" href="{{ $downloadUrl }}" target="_blank" rel="noopener" variant="primary" iconName="arrow-down-tray" class="px-4 py-2 text-sm">
                            Download Saved Form
                        </x-button>
                    </div>
                @endif

                <div class="pt-4 flex justify-center">
                    <x-button as="a" href="{{ route('admin.manpower.requests.index') }}" variant="secondary" iconName="arrow-left">
                        Back to Manpower Requests
                    </x-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
