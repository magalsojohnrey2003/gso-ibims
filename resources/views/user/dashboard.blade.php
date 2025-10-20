<x-app-layout>
 
    <x-title level="h2"
         size="2xl"
         weight="bold"
         icon="home"
         variant="s"
         iconStyle="circle"
         iconBg="gov-accent"
         iconColor="white">
    Dashboard
</x-title>
    <div class="py-10">
        
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
          
            {{-- Quick Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-box-open text-indigo-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">My Borrowed Items (Active)</p>
                        <p class="text-2xl font-bold">{{ $myBorrowedCount ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-hourglass-half text-yellow-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Pending Requests</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ $pendingReq ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-undo text-green-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Returned Items</p>
                        <p class="text-2xl font-bold text-green-600">{{ $returnedReq ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-5 flex items-center gap-4">
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Declined Requests</p>
                        <p class="text-2xl font-bold text-red-600">{{ $rejectedReq ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Two-column: Trends + Available Items --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Personal Borrow Trends --}}
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">📈 My Borrow Trends</h3>
                        <select id="personalTrendFilter" class="border rounded px-3 py-1 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month" selected>Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>
                    <div class="h-56">
                        <canvas id="personalBorrowChart"></canvas>
                        <p id="personalBorrowEmpty" class="text-gray-500 text-sm mt-2 hidden"></p>
                    </div>
                </div>

                {{-- Available Items Preview --}}
                <div class="card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">📦 Available Items</h3>
                        <a href="{{ route('borrow.items') }}" class="text-sm underline">See all items</a>
                    </div>
                    <div id="available-items" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if(isset($availableItemsPreview) && count($availableItemsPreview))
                            @foreach($availableItemsPreview as $item)
                                <div class="p-3 border rounded flex items-center gap-3">
                                    <div class="w-14 h-14 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                        @if(!empty($item['photo']))
                                            <img src="{{ asset('storage/'.$item['photo']) }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $item['name'] ?? 'Unknown' }}</div>
                                        <div class="text-xs opacity-70">{{ $item['category'] ?? '' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500">No items preview available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <style>
      .card { border-radius: .75rem; box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
    </style>

    @vite(['resources/js/app.js'])
</x-app-layout>
