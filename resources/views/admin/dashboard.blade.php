{{-- resources/views/admin/dashboard.blade.php --}}
<x-app-layout>
    <div class="py-10">
        <div class="sm:px-6 lg:px-8 space-y-10">
            <div class="px-6 lg:px-8">
                <x-title 
                    level="h2"
                    size="2xl"
                    weight="bold"
                    icon="home"
                    variant="s"
                    iconStyle="plain"
                    iconColor="title-purple"
                    iconBg="transparent">
                    Dashboard
                </x-title>
            </div>
            {{-- Welcome Card - Always shows until logout --}}
            @php
                $user = auth()->user();
                $firstName = $user->first_name ? trim($user->first_name) : '';
                $lastName = $user->last_name ? trim($user->last_name) : '';
                $fullName = trim($firstName . ' ' . $lastName);
                
                $hour = now()->hour;
                $timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                
                // Check if account is brand new (created today or within 24 hours)
                $accountAge = $user->created_at ? $user->created_at->diffInHours(now()) : 999;
                $isNewAccount = $accountAge < 24;
                
                if ($isNewAccount) {
                    // New account - show simple welcome message with full name
                    $welcomeMessage = $fullName 
                        ? "Welcome, {$fullName}! We're excited to have you here." 
                        : "Welcome! We're excited to have you here.";
                    $greetingType = 'new';
                } else {
                    // Account is older than 1 day - show time-based greeting with full name
                    $welcomeMessage = $fullName 
                        ? "{$timeGreeting}, {$fullName}! Welcome back." 
                        : "{$timeGreeting}! Welcome back.";
                    $greetingType = 'returning';
                }
            @endphp
            
            <x-welcome-card 
                :message="$welcomeMessage" 
                :userName="$fullName" 
                :type="$greetingType" 
            />
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {{-- Total Items --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-6 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-16 h-16 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-boxes text-white dark:text-white text-3xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">Total Items</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $totalItems ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Available Items --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-6 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-16 h-16 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-check-circle text-white dark:text-white text-3xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">Available Items</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $availableItems ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Borrow Requests --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-6 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-16 h-16 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-clipboard-list text-white dark:text-white text-3xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">Total Borrow Requests</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $totalBorrowReq ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts Section (two columns) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Borrow Trends --}}
                <div class="card p-6 chart-container rounded-xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold flex items-center gap-2 dark:text-white">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Borrow Trends
                        </h3>

                        <select id="trendFilter" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-3 py-1 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month" selected>Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>

                    <div class="h-72">
                        <canvas id="borrowTrendsChart"></canvas>
                        <p id="borrowTrendsEmpty" class="text-gray-500 dark:text-gray-400 text-sm mt-2 hidden">No data available</p>
                    </div>
                </div>

                {{-- Most Borrowed Items --}}
                <div class="card p-6 chart-container rounded-xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold flex items-center gap-2 dark:text-white">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Most Borrowed Items
                        </h3>
                        <select id="itemCategoryFilter" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-3 py-1 text-sm">
                            <option value="">All Categories</option>
                            @foreach($categories ?? [] as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="h-72">
                        <canvas id="itemUsageChart"></canvas>
                        <p id="itemUsageEmpty" class="text-gray-500 dark:text-gray-400 text-sm mt-2 hidden">No data available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pass data to JavaScript --}}
    <script id="dashboard-data" type="application/json">
        {
            "borrowLabels": {!! json_encode(array_keys($borrowTrends ?? [])) !!},
            "borrowData": {!! json_encode(array_values($borrowTrends ?? [])) !!},
            "itemLabels": {!! json_encode($itemUsage->pluck('name')->toArray() ?? []) !!},
            "itemData": {!! json_encode($itemUsage->pluck('total')->toArray() ?? []) !!},
            "endpoints": {
                "borrowTrends": "{{ route('admin.dashboard.borrow-trends') }}",
                "mostBorrowed": "{{ route('admin.dashboard.most-borrowed') }}"
            }
        }
    </script>

    @vite(['resources/js/app.js'])

</x-app-layout>
