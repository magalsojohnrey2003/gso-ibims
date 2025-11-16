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
          
            {{-- Quick Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- My Borrowed Items --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-5 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-14 h-14 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-box-open text-white dark:text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">My Borrowed Items (Active)</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $myBorrowedCount ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Pending Requests --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-5 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-14 h-14 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-hourglass-half text-white dark:text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">Pending Requests</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $pendingReq ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Returned Items --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-5 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-14 h-14 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-undo text-white dark:text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">Returned Items</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $returnedReq ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Declined Requests --}}
                <div class="stat-card relative overflow-hidden rounded-xl p-5 transition-all duration-300 hover:shadow-xl" 
                     style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <div class="flex items-center gap-4">
                        <div class="stat-icon-wrapper flex-shrink-0 w-14 h-14 rounded-full bg-white/20 dark:bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-times-circle text-white dark:text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-white/90 dark:text-white/80 mb-1">Declined Requests</p>
                            <p class="text-3xl font-bold text-white dark:text-white">{{ $rejectedReq ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Two-column: Trends + Available Items --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Personal Borrow Trends --}}
                <div class="card p-6 rounded-xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold flex items-center gap-2 dark:text-white">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            My Borrow Trends
                        </h3>
                        <select id="personalTrendFilter" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded px-3 py-1 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month" selected>Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>
                    <div class="h-56">
                        <canvas id="personalBorrowChart"></canvas>
                        <p id="personalBorrowEmpty" class="text-gray-500 dark:text-gray-400 text-sm mt-2 hidden">No data available</p>
                    </div>
                </div>

                {{-- Available Items Preview --}}
                <div class="card p-6 rounded-xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold flex items-center gap-2 dark:text-white">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Available Items
                        </h3>
                        <a href="{{ route('borrow.items') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">See all</a>
                    </div>
                    <div class="space-y-3 max-h-56 overflow-y-auto">
                        @if(isset($availableItemsPreview) && count($availableItemsPreview) > 0)
                            @foreach($availableItemsPreview as $item)
                                @php
                                    $photoUrl = null;
                                    if (!empty($item['photo'])) {
                                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($item['photo'])) {
                                            $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($item['photo']);
                                        } elseif (str_starts_with($item['photo'], 'http')) {
                                            $photoUrl = $item['photo'];
                                        } elseif (file_exists(public_path($item['photo']))) {
                                            $photoUrl = asset($item['photo']);
                                        }
                                    }
                                @endphp
                                <div class="p-3 border dark:border-gray-600 rounded-lg flex items-center gap-3 hover:shadow-md transition-shadow">
                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                        @if($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="{{ $item['name'] ?? '' }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700">
                                                <i class="fas fa-box text-gray-400 dark:text-gray-500 text-lg"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium dark:text-white truncate" title="{{ $item['name'] ?? 'Unknown' }} - {{ $item['category'] ?? 'Uncategorized' }}">
                                                {{ $item['name'] ?? 'Unknown' }} - {{ $item['category'] ?? 'Uncategorized' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">
                                            <i class="fas fa-check-circle"></i> {{ $item['available_qty'] ?? 0 }} available
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No items available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <style>
      .card { border-radius: .75rem; box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
      .stat-card { box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
      .stat-card:hover { transform: translateY(-2px); }
    </style>

    {{-- Pass initial data to JavaScript for charts --}}
    <script id="user-dashboard-data" type="application/json">
        {
            "myBorrowTrends": {!! json_encode($myBorrowTrends ?? []) !!}
        }
    </script>

    {{-- Load Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    @vite(['resources/js/app.js'])
</x-app-layout>
