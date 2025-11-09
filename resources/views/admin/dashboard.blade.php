{{-- resources/views/admin/dashboard.blade.php --}}
<x-app-layout>
<div class="px-6 lg:px-8">
    <x-title 
        level="h2"
        size="2xl"
        weight="bold"
        icon="home"
        variant="s"
        iconStyle="circle"
        iconBg="gov-accent"
        iconColor="white">
        Dashboard
    </x-title>
</div>
   

    <div class="py-10">
        <div class="sm:px-6 lg:px-8 space-y-10">

            {{-- Welcome Card - Shows after login --}}
            @if(session('status') === 'login-success' && session('login_message'))
                <x-welcome-card 
                    :message="session('login_message')" 
                    :userName="session('user_name', auth()->user()->first_name)" 
                    :type="session('greeting_type', 'returning')" 
                />
            @endif
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="card p-6 flex items-center gap-4">
                    <i class="fas fa-boxes text-indigo-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Total Items</p>
                        <p class="text-3xl font-bold">{{ $totalItems ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-6 flex items-center gap-4">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Available Items</p>
                        <p class="text-3xl font-bold">{{ $availableItems ?? 0 }}</p>
                    </div>
                </div>

                <div class="card p-6 flex items-center gap-4">
                    <i class="fas fa-clipboard-list text-yellow-500 text-3xl"></i>
                    <div>
                        <p class="text-sm opacity-70">Total Borrow Requests</p>
                        <p class="text-3xl font-bold">{{ $totalBorrowReq ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Borrow Requests Breakdown --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card p-6 text-center">
                    <p class="text-sm opacity-70">Pending Requests</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $pendingReq ?? 0 }}</p>
                </div>
                <div class="card p-6 text-center">
                    <p class="text-sm opacity-70">Approved Requests</p>
                    <p class="text-3xl font-bold text-green-600">{{ $approvedReq ?? 0 }}</p>
                </div>
                <div class="card p-6 text-center">
                    <p class="text-sm opacity-70">Declined Requests</p>
                    <p class="text-3xl font-bold text-red-600">{{ $rejectedReq ?? 0 }}</p>
                </div>
            </div>

            {{-- Charts Section --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Borrow Trends --}}
                <div class="card p-6 chart-container">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">📈 Borrow Trends</h3>

                        <select id="trendFilter" class="border rounded px-3 py-1 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month" selected>Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>

                    <div class="h-72">
                        <canvas id="borrowTrendsChart"></canvas>
                        <p id="borrowTrendsEmpty" class="text-gray-500 text-sm mt-2 hidden"></p>
                    </div>
                </div>

                {{-- Most Borrowed Items --}}
                <div class="card p-6 chart-container">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">📦 Most Borrowed Items</h3>
                        <select id="itemCategoryFilter" class="border rounded px-3 py-1 text-sm">
                            <option value="">All Categories</option>
                            @foreach($categories ?? [] as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="h-72">
                        <canvas id="itemUsageChart"></canvas>
                        <p id="itemUsageEmpty" class="text-gray-500 text-sm mt-2 hidden"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/app.js')

</x-app-layout>
