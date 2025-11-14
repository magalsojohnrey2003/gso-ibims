<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo2.png') }}">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="user-id" content="{{ auth()->id() ?? '' }}">
    <meta name="user-role" content="{{ auth()->user()->role ?? '' }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts / Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Minimal fallbacks & small layout helpers so the page looks correct until Tailwind rebuild runs -->
    <style>
        :root {
            /* gov accent for UI (used only as accent/background; not for primary text) */
            --gov-accent: #A855F7;
        }

        /* Ensure body picks up theme variables defined in resources/css/app.css if present.
           These preserve the "original | light | dark" variable approach used elsewhere. */
        body {
            background-color: var(--bg, #f4f6f8);
            color: var(--text, #0f1724);
            transition: background-color .25s ease, color .25s ease;
        }

        /* Page header: small, clean, minimal separator */
        .page-header {
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            background: transparent;
        }

        /* Content card helper (use when you want a card inside main) */
        .content-card {
            background: var(--card-bg, #fff);
            color: var(--text, #0f1724);
            border-radius: .5rem;
            padding: 1rem;
            box-shadow: var(--elev-shadow, 0 8px 20px rgba(0,0,0,0.06));
        }

        /* Ensure html and body fill full height */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Flex container fills viewport height */
        .min-h-screen {
            min-height: 100vh;
        }

        /* Page container flexbox for sidebar + main content */
        #pageContainer {
            display: flex;
            flex: 1 1 auto;
            padding-top: 4rem; /* height of fixed nav */
            overflow: hidden;
            height: calc(100vh - 4rem); /* fill remaining viewport height */
        }

        /* Sidebar fills height */
        #sidebar {
            height: 100%;
            overflow-y: auto; /* allow sidebar scrolling if content overflows */
        }

        /* Main content scrolls vertically */
        #mainContent {
            flex: 1 1 auto;
            overflow-y: auto;
            height: 100%;
        }

        /* Light, scoped live wallpaper inside page content only */
        #mainContent.main-wallpaper {
            position: relative;
            background: transparent;
            isolation: isolate; /* ensures pseudo-elements layer beneath content */
        }
        #mainContent.main-wallpaper::before,
        #mainContent.main-wallpaper::after {
            content: "";
            position: absolute;
            z-index: 0;
            pointer-events: none;
            filter: blur(40px);
            opacity: 0.22;
            transform: translateZ(0);
            will-change: transform;
        }
        /* top-left soft glow */
        #mainContent.main-wallpaper::before {
            width: 520px; height: 520px;
            top: -60px; left: -120px;
            background: radial-gradient(circle at 40% 40%, rgba(168,85,247,0.35), rgba(168,85,247,0.16) 40%, transparent 70%);
            animation: mcFloatA 18s ease-in-out infinite alternate;
        }
        /* bottom-right soft glow */
        #mainContent.main-wallpaper::after {
            width: 580px; height: 580px;
            right: -160px; bottom: -120px;
            background: radial-gradient(circle at 60% 60%, rgba(126,34,206,0.30), rgba(126,34,206,0.14) 42%, transparent 72%);
            animation: mcFloatB 22s ease-in-out infinite alternate;
        }
        @keyframes mcFloatA { 0%{ transform: translate3d(0,0,0) } 100%{ transform: translate3d(2%,1%,0) } }
        @keyframes mcFloatB { 0%{ transform: translate3d(0,0,0) } 100%{ transform: translate3d(-1.5%,-2%,0) } }
        @media (prefers-reduced-motion: reduce) {
            #mainContent.main-wallpaper::before,
            #mainContent.main-wallpaper::after { animation: none; opacity: 0.16; }
        }
    </style>
</head>

<body class="font-sans antialiased transition-all duration-300" data-theme="original">
    @php
        $loginMessage = null;
        if (session()->has('login_message')) {
            $loginMessage = session('login_message');
        } elseif (session()->has('login_success')) {
            $loginMessage = session('login_success');
        } elseif (session('status') === 'login-success') {
            $loginMessage = session('login_message') ?? 'Login successful!';
        }
        $loginMessage = $loginMessage ? (string) $loginMessage : '';
    @endphp

<div class="min-h-screen flex flex-col">
    <!-- Navigation (fixed) -->
    @include('layouts.navigation')

    <!-- Page container: give top padding equal to nav height so content sits below fixed nav -->
    <div id="pageContainer" class="flex flex-1 pt-16 overflow-hidden">
        <!-- Sidebar: role-based includes (keeps same classes) -->
        @auth
            @if(request()->routeIs('profile.*'))
                @include('layouts.sidebar-profile')
            @else
                @if(auth()->user()->role === 'admin')
                    @include('layouts.sidebar-admin')
                @else
                    @include('layouts.sidebar-user')
                @endif
            @endif
        @endauth

        <!-- Main content area: allow vertical scrolling inside this column only -->
        <main id="mainContent" tabindex="-1" class="flex-1 {{ isset($noMainScroll) && $noMainScroll ? 'overflow-y-visible' : 'overflow-y-auto' }}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>


<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" aria-hidden="true"></div>

    <!-- Scripts -->
    <script>
        /* ======================================
           THEME HANDLING (global function)
           - class-based dark mode via html.dark so Breeze + Tailwind dark utilities work.
           - supports mode values: 'original' | 'light' | 'dark'
           ====================================== */
        function applyTheme(mode) {
            const body = document.body;
            const html = document.documentElement;
            mode = (mode === 'light' || mode === 'dark') ? mode : 'original';

            // remove existing theme classes
            body.classList.remove('theme-original', 'theme-light', 'theme-dark');
            html.classList.remove('dark');

            if (mode === 'dark') {
                body.classList.add('theme-dark');
                html.classList.add('dark'); // Tailwind class-based dark mode
            } else if (mode === 'light') {
                body.classList.add('theme-light');
            } else {
                body.classList.add('theme-original');
            }

            try { body.setAttribute('data-theme', mode); } catch (e) {}
            try { window.dispatchEvent(new CustomEvent('themeChanged', { detail: { mode } })); } catch (e) {}
        }

        // Initialize theme from localStorage or fallback to 'original'
        document.addEventListener('DOMContentLoaded', () => {
            try {
                const savedTheme = localStorage.getItem('theme') || 'original';
                applyTheme(savedTheme);
            } catch (e) {
                applyTheme('original');
            }
        });

        // Observe body class changes and keep data-theme in sync (for other scripts)
        (function() {
            const body = document.body;
            if (!window.MutationObserver) return;
            const observer = new MutationObserver((mutations) => {
                for (const m of mutations) {
                    if (m.attributeName === 'class') {
                        const hasLight = body.classList.contains('theme-light');
                        const hasDark = body.classList.contains('theme-dark');
                        const mode = hasDark ? 'dark' : (hasLight ? 'light' : 'original');
                        if (body.getAttribute('data-theme') !== mode) {
                            try { body.setAttribute('data-theme', mode); } catch (e) {}
                            try { window.dispatchEvent(new CustomEvent('themeChanged', { detail: { mode } })); } catch (e) {}
                        }
                        break;
                    }
                }
            });
            observer.observe(body, { attributes: true, attributeFilter: ['class'] });
        })();
    </script>

    {{-- Global Toast Notification Component (Alpine.js) --}}
    <div x-data="globalToast" 
         @toast.window="showToast($event.detail.message, $event.detail.type, $event.detail.title)"
         class="fixed top-6 right-6 z-[9999]">
        <div 
            x-show="show"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-y-[-100%] opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            :class="{ 'shake': shakeAnimation }"
            @mouseenter="pauseTimer()"
            @mouseleave="resumeTimer()"
            class="bg-purple-600 text-white rounded-lg shadow-2xl min-w-[320px] max-w-md overflow-hidden"
        >
            {{-- Main Content --}}
            <div class="p-4">
                <div class="flex items-start gap-3">
                    {{-- Success Icon --}}
                    <template x-if="type === 'success'">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#10B981" stroke="#10B981" stroke-width="2"/>
                                <path d="M8 12.5L10.5 15L16 9.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </template>

                    {{-- Error Icon --}}
                    <template x-if="type === 'error'">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#EF4444" stroke="#EF4444" stroke-width="2"/>
                                <path d="M15 9L9 15M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </template>

                    {{-- Warning Icon --}}
                    <template x-if="type === 'warning'">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#F59E0B" stroke="#F59E0B" stroke-width="2"/>
                                <path d="M12 7V13M12 16H12.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </template>

                    {{-- Info Icon --}}
                    <template x-if="type === 'info'">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#3B82F6" stroke="#3B82F6" stroke-width="2"/>
                                <path d="M12 11V17M12 7H12.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </template>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-white mb-1" x-text="title"></div>
                        <div class="text-sm text-gray-300 leading-relaxed" x-text="message"></div>
                    </div>

                    {{-- Close Button --}}
                    <button
                        type="button"
                        @click="close()"
                        class="flex-shrink-0 text-gray-400 hover:text-white transition-colors"
                        aria-label="Close notification"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="h-1 bg-purple-700">
                <div 
                    x-ref="progressbar"
                    class="h-full transition-all"
                    :class="{
                        'bg-green-400': type === 'success',
                        'bg-red-400': type === 'error',
                        'bg-yellow-400': type === 'warning',
                        'bg-blue-400': type === 'info'
                    }"
                    style="width: 100%;"
                ></div>
            </div>
        </div>
    </div>

    {{-- Handle Laravel Session Flash Messages --}}
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.showToast === 'function') {
                    window.showToast(@json(session('success')), 'success');
                }
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.showToast === 'function') {
                    window.showToast(@json(session('error')), 'error');
                }
            });
        </script>
    @endif

    @if(session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.showToast === 'function') {
                    window.showToast(@json(session('warning')), 'warning');
                }
            });
        </script>
    @endif

    @if(session('info'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.showToast === 'function') {
                    window.showToast(@json(session('info')), 'info');
                }
            });
        </script>
    @endif

</body>
</html>
