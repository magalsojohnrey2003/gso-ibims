@php
    $statusValue = $request->status ?? 'pending';
    $statusLabel = ucwords(str_replace('_', ' ', $statusValue));
    $statusBadge = match ($statusValue) {
        'approved' => 'accepted',
        'delivered', 'returned' => 'success',
        'pending' => 'pending',
        default => 'info',
    };
@endphp

<x-app-layout>
    <div class="py-10 px-6 bg-orange-50 min-h-screen">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow-lg rounded-2xl p-8 text-center space-y-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas {{ $success ? 'fa-qrcode' : 'fa-info-circle' }} text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        QR Approval {{ $success ? 'Complete' : 'Already Processed' }}
                    </h1>
                    <p class="text-gray-600">
                        {{ $message }}
                    </p>
                </div>

                <div class="space-y-2 text-sm text-gray-600">
                    <div>
                        <span class="font-semibold text-gray-800">Request ID:</span>
                        <span class="ml-1 text-gray-700">#{{ $request->id }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-800">Borrower:</span>
                        <span class="ml-1 text-gray-700">{{ $request->borrower_name }}</span>
                    </div>
                    <div class="inline-flex items-center gap-2">
                        <span class="font-semibold text-gray-800">Current Status:</span>
                        <x-status-badge :type="$statusBadge" :text="$statusLabel" />
                    </div>
                    @if(isset($scanTimestamp))
                        <div>
                            <span class="font-semibold text-gray-800">Scan Timestamp:</span>
                            <span class="ml-1 text-gray-700">{{ $scanTimestamp->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                        </div>
                    @endif
                </div>

                <div class="pt-4 flex justify-center">
                    @if($success)
                        <x-button as="a" href="{{ route('admin.walkin.index') }}" variant="primary" iconName="clipboard-document-check">
                            View Walk-in Requests
                        </x-button>
                    @else
                        <x-button as="a" href="{{ route('admin.walkin.index') }}" variant="secondary" iconName="arrow-left">
                            Back to Walk-in Requests
                        </x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
