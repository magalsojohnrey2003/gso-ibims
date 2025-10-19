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

        /* Toast fallback positioning & small transition (JS will control visible state) */
        #toast {
            position: fixed;
            z-index: 99999;
            right: 20px;
            top: 18px;
            max-width: 420px;
            min-width: 160px;
            padding: 10px 14px;
            border-radius: 10px;
            transition: opacity 180ms ease, transform 180ms ease;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
        }
        #toast.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
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

        <main id="mainContent" tabindex="-1" class="flex-1 overflow-y-auto">
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


        /* ======================================
           TOAST HELPERS (positions toast under nav/main or falls back to top-right)
           - uses the same algorithms your earlier script expects (findPreferredAnchor etc.)
           ====================================== */
        function findPreferredAnchor(toast) {
            var explicit = toast.getAttribute('data-position-target');
            if (explicit) {
                try {
                    var el = document.querySelector(explicit);
                    if (el) return el;
                } catch (e) {}
            }

            var candidates = [
                '#contentWrapper main',
                'main',
                '#contentWrapper',
                '.page-header',
                '.dashboard-cards',
                '.stats-row',
                '.dashboard-stats',
                'nav'
            ];

            for (var i = 0; i < candidates.length; i += 1) {
                var sel = candidates[i];
                try {
                    var node = document.querySelector(sel);
                    if (node) return node;
                } catch (e) {}
            }

            return document.querySelector('nav') || null;
        }

        function measureToast(toast) {
            var clone = toast.cloneNode(true);
            clone.style.position = 'fixed';
            clone.style.left = '0';
            clone.style.top = '-9999px';
            clone.style.visibility = 'hidden';
            clone.classList.remove('hidden');
            clone.classList.remove('show');
            document.body.appendChild(clone);
            var rect = clone.getBoundingClientRect();
            document.body.removeChild(clone);
            return rect;
        }

        function positionToastUnderAnchor(toast) {
            try {
                var anchor = findPreferredAnchor(toast);
                var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
                var scrollY = window.pageYOffset || document.documentElement.scrollTop;

                var toastRect = measureToast(toast);

                if (!anchor) {
                    toast.style.left = 'auto';
                    toast.style.right = '18px';
                    toast.style.top = (12 + scrollY) + 'px';
                    return;
                }

                var aRect = anchor.getBoundingClientRect();
                var anchorLeft = Math.round(aRect.left + scrollX);
                var anchorRight = Math.round(aRect.right + scrollX);
                var anchorBottom = Math.round(aRect.bottom + scrollY);

                var top = anchorBottom + 8;
                toast.style.top = top + 'px';

                var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                var desiredLeft = anchorRight - toastRect.width - 12;
                var minLeft = Math.max(12, anchorLeft + 12);
                var maxLeft = Math.max(minLeft, viewportWidth - toastRect.width - 12);

                var left = Math.min(Math.max(desiredLeft, minLeft), maxLeft);
                toast.style.left = Math.round(left) + 'px';
                toast.style.right = 'auto';
            } catch (e) {
                try { toast.style.top = '12px'; toast.style.right = '18px'; toast.style.left = 'auto'; } catch (e) {}
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            if (!toast) return;

            toast.textContent = message;

            try {
                const cs = getComputedStyle(document.body);
                const primary = cs.getPropertyValue('--primary').trim() || '#4f46e5';
                const primary600 = cs.getPropertyValue('--primary-600').trim() || '#4338ca';
                const accent = cs.getPropertyValue('--accent').trim() || '#f59e0b';

                if (type === 'success') {
                    toast.style.background = `linear-gradient(90deg, ${primary}, ${primary600})`;
                    toast.style.color = '#ffffff';
                } else if (type === 'error') {
                    toast.style.background = 'linear-gradient(90deg, #ef4444, #b91c1c)';
                    toast.style.color = '#ffffff';
                } else if (type === 'warning') {
                    toast.style.background = `linear-gradient(90deg, ${accent}, ${primary})`;
                    toast.style.color = '#ffffff';
                } else {
                    toast.style.background = `linear-gradient(90deg, ${primary}, ${primary600})`;
                    toast.style.color = '#ffffff';
                }
            } catch (e) {
                toast.style.background = '';
                toast.style.color = '';
            }

            positionToastUnderAnchor(toast);

            toast.classList.remove('hidden');
            void toast.offsetWidth;
            toast.classList.add('show');

            clearTimeout(toast._hideTimeout);
            toast._hideTimeout = setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(function () {
                    toast.classList.add('hidden');
                    toast.style.left = '';
                    toast.style.top = '';
                    toast.style.background = '';
                    toast.style.color = '';
                }, 220);
            }, 4000);
        }


        /* ======================================
           Sidebar toggle + overlay handlers (keeps your IDs intact)
           ====================================== */
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleBtn = document.getElementById('sidebarToggle');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    if (!sidebar || window.innerWidth < 1024) {
                        if (sidebar) sidebar.classList.toggle('-translate-x-full');
                        if (overlay) overlay.classList.toggle('hidden');
                        return;
                    }
                    if (sidebar) {
                        sidebar.classList.toggle('w-64');
                        sidebar.classList.toggle('w-20');
                    }
                    document.querySelectorAll('.sidebar-text, .sidebar-logo .logo-img').forEach(el => {
                        el.classList.toggle('hidden');
                    });
                });
            }

            if (overlay) {
                overlay.addEventListener('click', () => {
                    if (sidebar) sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    if (sidebar) sidebar.classList.remove('-translate-x-full');
                    if (overlay) overlay.classList.add('hidden');
                }
            });
        });

        // Reposition toast on resize/scroll while visible
        window.addEventListener('resize', function () {
            var toastEl = document.getElementById('toast');
            if (toastEl && toastEl.classList.contains('show')) positionToastUnderAnchor(toastEl);
        }, { passive: true });

        window.addEventListener('scroll', function () {
            var toastEl = document.getElementById('toast');
            if (toastEl && toastEl.classList.contains('show')) positionToastUnderAnchor(toastEl);
        }, { passive: true });
    </script>
</body>
</html>
