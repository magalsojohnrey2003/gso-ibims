@props(['message' => '', 'userName' => '', 'type' => 'returning'])

<div class="welcome-card-wrapper mb-8 animate-fade-in">
    <div class="welcome-card relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-2xl shadow-2xl p-8 text-white transform transition-all duration-300 hover:scale-[1.02] hover:shadow-3xl">
        
        {{-- Animated Background Elements --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="welcome-circle welcome-circle-1"></div>
            <div class="welcome-circle welcome-circle-2"></div>
            <div class="welcome-circle welcome-circle-3"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    {{-- Icon and Badge --}}
                    <div class="flex items-center gap-3 mb-4">
                        @if($type === 'new')
                            <div class="welcome-icon bg-white/20 backdrop-blur-sm rounded-full p-3 animate-bounce-soft">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                                </svg>
                            </div>
                            <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold uppercase tracking-wide">
                                🎉 New User
                            </span>
                        @else
                            <div class="welcome-icon bg-white/20 backdrop-blur-sm rounded-full p-3 animate-pulse-soft">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                            </div>
                            <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold uppercase tracking-wide">
                                👋 Welcome Back
                            </span>
                        @endif
                    </div>

                    {{-- Main Message --}}
                    <div class="space-y-2">
                        <h2 class="text-3xl md:text-4xl font-bold tracking-tight animate-slide-up">
                            {{ $message ?: 'Welcome!' }}
                        </h2>
                        
                        @if($type === 'new')
                            <p class="text-white/90 text-base md:text-lg animate-slide-up animation-delay-100">
                                Let's get started with your first request! 🚀
                            </p>
                        @else
                            <p class="text-white/90 text-base md:text-lg animate-slide-up animation-delay-100">
                                Ready to continue where you left off? 
                            </p>
                        @endif

                        {{-- Current Date/Time Info --}}
                        <div class="flex items-center gap-2 text-white/80 text-sm mt-3 animate-slide-up animation-delay-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ now()->format('l, F j, Y • g:i A') }}</span>
                        </div>
                    </div>
                </div>

                {{-- User Avatar Placeholder --}}
                <div class="hidden md:block">
                    <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm border-4 border-white/30 flex items-center justify-center text-3xl font-bold animate-scale-in">
                        {{ strtoupper(substr($userName ?: 'U', 0, 1)) }}
                    </div>
                </div>
            </div>

            {{-- Quick Action Hint --}}
            @if($type === 'new')
            <div class="mt-6 pt-6 border-t border-white/20 animate-slide-up animation-delay-300">
                <div class="flex items-center gap-2 text-sm text-white/90">
                    <svg class="w-5 h-5 animate-bounce-soft animation-delay-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">Pro tip:</span>
                    <span>Browse available items below to create your first borrow request!</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Fade in animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes bounceSoft {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    @keyframes pulseSoft {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -30px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }

    .animate-fade-in {
        animation: fadeIn 0.6s ease-out;
    }

    .animate-slide-up {
        animation: slideUp 0.6s ease-out;
    }

    .animate-scale-in {
        animation: scaleIn 0.6s ease-out;
    }

    .animate-bounce-soft {
        animation: bounceSoft 2s ease-in-out infinite;
    }

    .animate-pulse-soft {
        animation: pulseSoft 2s ease-in-out infinite;
    }

    .animation-delay-100 {
        animation-delay: 0.1s;
        opacity: 0;
        animation-fill-mode: forwards;
    }

    .animation-delay-200 {
        animation-delay: 0.2s;
        opacity: 0;
        animation-fill-mode: forwards;
    }

    .animation-delay-300 {
        animation-delay: 0.3s;
        opacity: 0;
        animation-fill-mode: forwards;
    }

    .animation-delay-500 {
        animation-delay: 0.5s;
    }

    /* Floating background circles */
    .welcome-circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: float 20s infinite ease-in-out;
    }

    .welcome-circle-1 {
        width: 200px;
        height: 200px;
        top: -50px;
        right: -50px;
        animation-delay: 0s;
    }

    .welcome-circle-2 {
        width: 150px;
        height: 150px;
        bottom: -30px;
        left: -30px;
        animation-delay: 5s;
    }

    .welcome-circle-3 {
        width: 100px;
        height: 100px;
        top: 50%;
        left: 50%;
        animation-delay: 10s;
    }

    /* Enhanced shadow on hover */
    .welcome-card:hover {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .welcome-card {
            padding: 1.5rem;
        }
    }
</style>
