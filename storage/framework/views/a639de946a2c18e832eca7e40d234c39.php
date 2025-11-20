<nav x-data="{ open: false }"
    class="primary-navbar w-full fixed top-0 left-0 z-50 backdrop-blur-sm"
     role="navigation" aria-label="Primary">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="h-16 flex items-center justify-between">
            <!-- Left: Toggle + Brand -->
            <div class="flex items-center gap-3">
                <!-- Sidebar Toggle Button -->
                <button id="sidebarToggle"
                        aria-label="Toggle sidebar"
                        class="nav-icon-button touch-p-2 -ml-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 focus-visible:ring-offset-0"
                >
                    <i class="fas fa-bars text-lg" aria-hidden="true"></i>
                </button>

                <!-- Brand logo + title - responsive -->
                <a href="<?php echo e(auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard')) : url('/')); ?>"
                   class="flex items-center gap-2 no-underline nav-brand">
                    <img src="<?php echo e(asset('images/logo2.png')); ?>" class="h-8 w-8 object-contain" alt="logo">
                    <span class="text-lg font-bold leading-none nav-brand-text">GSO-IBIMS</span>
                </a>
            </div>

              <!-- Center: optional page title slot for narrow screens -->
            <div class="flex-1 flex items-center justify-center sm:justify-start">
                <!-- For mobile we can show a condensed page title if the template sets it via a section -->
                <div class="text-sm font-semibold nav-title truncate max-w-[60vw] sm:max-w-none">
                    <?php echo $__env->yieldContent('navTitle', ''); ?>
                </div>
            </div>

            <!-- Right side: controls -->
            <div class="flex items-center gap-3">
                <!-- Notification + profile on medium+ screens; on small screens we keep icons compact -->
                <div class="flex items-center gap-2">
                    <button id="notificationBtn" class="nav-icon-button relative w-9 h-9 rounded-full" aria-haspopup="true" aria-expanded="false" aria-controls="notificationDropdown">
                        <i class="fas fa-bell text-base" aria-hidden="true"></i>
                        <span id="notificationBadge" class="absolute -top-1 -right-1 inline-flex items-center justify-center rounded-full text-[10px] w-4 h-4 bg-red-600 text-white hidden">0</span>
                    </button>

            <!-- Notification Dropdown -->
            <div id="notificationDropdown"
                class="hidden opacity-0 scale-95 absolute right-4 top-full mt-3 w-80 sm:w-96 max-w-[calc(100vw-2rem)]
                        bg-white dark:bg-gray-800 shadow-2xl rounded-2xl overflow-hidden z-50"
                role="menu" aria-label="Notifications">

                <!-- triangular pointer -->
                <div class="absolute -top-3 right-8 w-6 h-6 bg-white dark:bg-gray-800 transform rotate-45 border-l border-t border-gray-200 dark:border-gray-700 z-40"></div>

                <!-- Header -->
                <div class="px-6 py-4 relative bg-white dark:bg-gray-800 border-b dark:border-gray-700 text-center">
                    <div class="text-xl font-semibold text-gray-900 dark:text-gray-100">Notifications</div>
                    <div class="absolute top-3 right-4">
                        <button id="markAllReadBtn" class="text-xs text-gray-500 hover:underline">Mark all read</button>
                    </div>
                </div>

                <!-- List (scrollable) -->
                <div id="notificationList" class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800 p-2">
                    <p class="px-4 py-4 text-sm text-gray-500 dark:text-gray-300 m-0">No notifications yet</p>
                </div>

                <!-- Footer (Load more) -->
                <div class="px-3 py-3 border-t dark:border-gray-700 bg-white dark:bg-gray-800">
                    <button id="loadMoreNotifications" class="w-full py-3 rounded-md bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold">
                        Load more
                    </button>
                </div>
            </div>

            <!-- Notification modal -->
            <div id="notificationModal" class="fixed inset-0 z-60 hidden items-center justify-center">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="notificationModalBackdrop"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-[min(720px,95%)] overflow-hidden">
                    <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center justify-between">
                        <h3 id="notificationModalTitle" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notification</h3>
                        <button id="notificationModalClose" class="text-gray-500 hover:text-gray-700 dark:text-gray-300">&times;</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div id="notificationModalMessage" class="text-sm text-gray-700 dark:text-gray-300"></div>

                        <div id="notificationModalItems" class="space-y-1">
                            <!-- JS will populate item rows -->
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-300">
                            <div><strong>Borrow Date:</strong> <span id="notificationModalBorrowDate">—</span></div>
                            <div><strong>Return Date:</strong> <span id="notificationModalReturnDate">—</span></div>
                        </div>

                        <div id="notificationModalReason" class="text-sm text-red-600 dark:text-red-400"></div>
                    </div>

                    <div class="px-6 py-3 border-t dark:border-gray-700 flex justify-end gap-2">
                        <button id="notificationModalClose2" class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile -->
        <div class="relative">
            <button id="profileDropdownBtn" class="flex items-center nav-profile-trigger focus:outline-none" aria-haspopup="true" aria-expanded="false" aria-controls="profileDropdown">
                <div class="w-9 h-9 rounded-full overflow-hidden border-2 profile-avatar transition shadow-sm">
                    <img src="<?php echo e(Auth::user()->profile_photo ? asset('storage/' . Auth::user()->profile_photo) : asset('images/profile.jpg')); ?>"
                        alt="Profile" class="w-full h-full object-cover">
                </div>
            </button>

            <!-- Dropdown Menu -->
            <div id="profileDropdown"
                 class="hidden opacity-0 scale-95 absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                 role="menu" aria-label="User menu">
                <!-- Profile Info -->
                <div class="px-4 py-3 flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-purple-500 shadow-md">
                        <img src="<?php echo e(Auth::user()->profile_photo
                                        ? asset('storage/' . Auth::user()->profile_photo)
                                        : asset('images/profile.jpg')); ?>"
                             alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo e(Auth::user()->first_name); ?></p>
                        <a href="<?php echo e(route('profile.info')); ?>" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">See Profile</a>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- THEME SWITCH -->
                <div class="px-4 py-2">
                    <span class="block text-sm font-semibold text-black dark:text-gray-200">Appearance</span>

                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <!-- Accessible toggle switch -->
                            <button id="profileThemeToggle"
                                    type="button"
                                    role="switch"
                                    aria-checked="false"
                                    aria-label="Toggle dark mode"
                                    class="relative inline-flex items-center h-7 w-14 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                                    >
                                <!-- track -->
                                <span id="profileThemeToggleTrack"
                                      class="absolute inset-0 rounded-full bg-gray-200 dark:bg-gray-700 transition-colors"
                                      aria-hidden="true"></span>

                                <!-- knob -->
                                <span id="profileThemeToggleKnob"
                                      class="absolute left-1 top-1/2 -translate-y-1/2 h-5 w-5 bg-white rounded-full shadow transition-transform transform flex items-center justify-center"
                                      aria-hidden="true">
                                    <i id="profileThemeIcon" class="fas fa-moon text-xs text-gray-600" aria-hidden="true"></i>
                                </span>
                            </button>

                            <div>
                                <span class="text-sm text-black dark:text-gray-200">Dark Mode</span>
                            </div>
                        </div>

                        <div id="profileThemeLabel" class="text-xs text-gray-500 dark:text-gray-300 flex items-center">—</div>
                    </div>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <!-- Logout -->
                <div class="px-2 py-2">
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit"
                            class="flex items-center space-x-2 w-full px-3 py-2 rounded-md
                                   text-red-600 dark:text-red-400
                                   hover:bg-red-100 dark:hover:bg-red-800
                                   hover:text-red-700 dark:hover:text-red-200
                                   font-semibold transition">
                            <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts: dropdown animation + theme toggle wiring -->
    <script>
        (function () {
            // --- Helpers for dropdown show/hide with small fade/scale animation ---
            function showDropdown(dropdown, triggerBtn) {
                if (!dropdown) return;

                // Remove any closing flag or handler
                if (dropdown._closingHandler) {
                    dropdown.removeEventListener('transitionend', dropdown._closingHandler);
                    dropdown._closingHandler = null;
                }

                // If already visible and fully shown, bail
                if (!dropdown.classList.contains('hidden') && dropdown.classList.contains('opacity-100')) {
                    if (triggerBtn) triggerBtn.setAttribute('aria-expanded', 'true');
                    return;
                }

                // Prepare for animation
                dropdown.classList.remove('hidden');
                // start from small + transparent
                dropdown.classList.remove('opacity-100','scale-100','transition','duration-150','ease-out');
                dropdown.classList.add('opacity-0','scale-95');

                // force reflow then start the enter animation
                requestAnimationFrame(() => {
                    dropdown.classList.remove('opacity-0','scale-95');
                    dropdown.classList.add('opacity-100','scale-100','transition','duration-150','ease-out');
                });

                if (triggerBtn) triggerBtn.setAttribute('aria-expanded', 'true');
            }

            function hideDropdown(dropdown, triggerBtn) {
                if (!dropdown) return;
                // If already hidden, ensure attribute and bail
                if (dropdown.classList.contains('hidden')) {
                    if (triggerBtn) triggerBtn.setAttribute('aria-expanded', 'false');
                    return;
                }

                // Start exit animation: switch to small + transparent
                dropdown.classList.remove('opacity-100','scale-100');
                dropdown.classList.add('opacity-0','scale-95','transition','duration-150','ease-in');

                // remove after transition end
                const onEnd = function (e) {
                    // ensure the event is for our element (not bubbled children)
                    if (e && e.target !== dropdown) return;
                    dropdown.classList.add('hidden');
                    // cleanup classes to keep DOM tidy
                    dropdown.classList.remove('opacity-0','scale-95','transition','duration-150','ease-in');
                    dropdown._closingHandler = null;
                    dropdown.removeEventListener('transitionend', onEnd);
                };

                // store handler so it can be removed if another animation runs
                if (dropdown._closingHandler) {
                    dropdown.removeEventListener('transitionend', dropdown._closingHandler);
                }
                dropdown._closingHandler = onEnd;
                dropdown.addEventListener('transitionend', onEnd);

                if (triggerBtn) triggerBtn.setAttribute('aria-expanded', 'false');

                // Fallback: if transitionend didn't fire (older browsers), ensure hidden after 220ms
                clearTimeout(dropdown._fallbackTimeout);
                dropdown._fallbackTimeout = setTimeout(() => {
                    if (!dropdown.classList.contains('hidden')) {
                        dropdown.classList.add('hidden');
                        dropdown.classList.remove('opacity-0','scale-95','transition','duration-150','ease-in');
                    }
                    if (dropdown._closingHandler) {
                        dropdown.removeEventListener('transitionend', dropdown._closingHandler);
                        dropdown._closingHandler = null;
                    }
                }, 300);
            }

            // --- Dropdown wiring (notifications + profile) ---
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            const notifBtn = document.getElementById('notificationBtn');
            const notifDropdown = document.getElementById('notificationDropdown');

            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const willShow = profileDropdown.classList.contains('hidden');
                    if (willShow) {
                        // open profile; close notifications if open
                        if (notifDropdown && !notifDropdown.classList.contains('hidden')) hideDropdown(notifDropdown, notifBtn);
                        showDropdown(profileDropdown, profileBtn);
                        // sync theme UI when opened
                        if (typeof syncProfileToggleUI === 'function') syncProfileToggleUI();
                    } else {
                        hideDropdown(profileDropdown, profileBtn);
                    }
                });
            }

            if (notifBtn) {
                notifBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const willShow = notifDropdown.classList.contains('hidden');
                    if (willShow) {
                        // open notifications; close profile if open
                        if (profileDropdown && !profileDropdown.classList.contains('hidden')) hideDropdown(profileDropdown, profileBtn);
                        showDropdown(notifDropdown, notifBtn);
                    } else {
                        hideDropdown(notifDropdown, notifBtn);
                    }
                });
            }

            // Close dropdowns when clicking outside
            window.addEventListener('click', (e) => {
                if (profileBtn && profileDropdown && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                    hideDropdown(profileDropdown, profileBtn);
                }
                if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                    hideDropdown(notifDropdown, notifBtn);
                }
            });

            // Also close on Escape
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (profileDropdown && !profileDropdown.classList.contains('hidden')) hideDropdown(profileDropdown, profileBtn);
                    if (notifDropdown && !notifDropdown.classList.contains('hidden')) hideDropdown(notifDropdown, notifBtn);
                }
            });

            // --- Theme toggle wiring (keeps previous behavior) ---
            const toggleBtn = document.getElementById('profileThemeToggle');
            const knob = document.getElementById('profileThemeToggleKnob');
            const track = document.getElementById('profileThemeToggleTrack');
            const icon = document.getElementById('profileThemeIcon');
            const label = document.getElementById('profileThemeLabel');

            // fallback gov color values (used when Tailwind class not compiled yet)
            const GOV_HEX = '#A855F7';
            const GOV_RGBA = '168,85,247';

            // Determine theme: saved -> html.class -> system
            function getCurrentTheme() {
                try {
                    const saved = localStorage.getItem('theme');
                    if (saved === 'dark') return 'dark';
                    if (saved === 'light') return 'light';
                } catch (e) { /* ignore */ }

                if (document.documentElement.classList.contains('dark')) return 'dark';
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
                return 'light';
            }

            // Apply theme: prefer user helpers if available
            function applyThemeChoice(isDark) {
                try {
                    try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
                    if (typeof window.setTheme === 'function') {
                        window.setTheme(isDark ? 'dark' : 'light');
                        return;
                    }
                    if (typeof window.applyTheme === 'function') {
                        window.applyTheme(isDark ? 'dark' : 'light');
                        return;
                    }
                    // Fallback: toggle html.dark
                    if (isDark) document.documentElement.classList.add('dark');
                    else document.documentElement.classList.remove('dark');

                    // Broadcast change
                    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { mode: isDark ? 'dark' : 'light' } }));
                } catch (e) { console.error(e); }
            }

            // Update UI display of the switch (knob, track color, icon, label, aria)
            function updateProfileThemeToggleUI(isDark) {
                if (!toggleBtn || !knob || !track || !icon || !label) return;

                toggleBtn.setAttribute('aria-checked', isDark ? 'true' : 'false');

                // knob position
                if (isDark) {
                    knob.classList.add('translate-x-7');
                } else {
                    knob.classList.remove('translate-x-7');
                }

                // icon inside knob
                if (isDark) {
                    icon.className = 'fas fa-sun text-xs text-yellow-400';
                } else {
                    icon.className = 'fas fa-moon text-xs text-gray-600';
                }

                // track color: try Tailwind class toggle; fallback to inline color
                if (isDark) {
                    // prefer class (if compiled)
                    track.classList.remove('bg-gray-200');
                    track.classList.add('bg-gov-purple');
                    // fallback inline for safety
                    track.style.backgroundColor = GOV_HEX;
                    label.textContent = 'Dark';
                } else {
                    track.classList.remove('bg-gov-purple');
                    track.classList.add('bg-gray-200');
                    track.style.backgroundColor = '';
                    label.textContent = 'Light';
                }
            }

            // Sync UI to actual theme state
            function syncProfileToggleUI() {
                const mode = getCurrentTheme();
                updateProfileThemeToggleUI(mode === 'dark');
            }

            // Toggle action (click and keyboard)
            function toggleFromUI() {
                const currentlyDark = (getCurrentTheme() === 'dark');
                const willBeDark = !currentlyDark;
                applyThemeChoice(willBeDark);
                updateProfileThemeToggleUI(willBeDark);
            }

            if (toggleBtn) {
                // click
                toggleBtn.addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    toggleFromUI();
                });

                // keyboard support (Space/Enter)
                toggleBtn.addEventListener('keydown', function (ev) {
                    if (ev.key === ' ' || ev.key === 'Enter') {
                        ev.preventDefault();
                        ev.stopPropagation();
                        toggleFromUI();
                    }
                });

                // Listen for theme changes from other code paths and sync
                window.addEventListener('themeChanged', function (ev) {
                    const mode = ev && ev.detail && ev.detail.mode ? ev.detail.mode : null;
                    if (mode) updateProfileThemeToggleUI(mode === 'dark');
                    else syncProfileToggleUI();
                });

                // initial sync on load
                document.addEventListener('DOMContentLoaded', function () {
                    syncProfileToggleUI();
                });
            }
        })();
    </script>
</nav>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/layouts/navigation.blade.php ENDPATH**/ ?>