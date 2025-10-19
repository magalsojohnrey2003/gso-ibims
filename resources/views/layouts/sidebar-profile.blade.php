<!-- resources/views/layouts/sidebar-profile.blade.php -->
<aside id="sidebar"
    class="w-64 sidebar h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="profile"
    aria-hidden="true">

    <!-- Header -->
    <div class="flex items-center justify-between p-5">
        <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard') }}"
           class="flex items-center space-x-2 sidebar-logo">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo" class="h-8 w-8 object-contain logo-img">
            <span class="text-lg font-bold sidebar-text">GSO-IBIMS</span>
        </a>
        <button id="sidebarToggle"
            class="p-2 rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 transition">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-6">
        <ul class="space-y-2">
            <!-- Back to Home -->
            <li>
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard') }}"
                   class="flex items-center px-4 py-3 rounded-md transition hover:bg-gradient-to-r hover:from-indigo-500 hover:to-indigo-600 hover:text-white group
                          {{ request()->routeIs('admin.dashboard') || request()->routeIs('user.dashboard') ? 'bg-indigo-600 text-white' : '' }}">
                    <i class="fas fa-home mr-3 text-indigo-500 group-hover:text-white"></i>
                    <span class="sidebar-text">Home</span>
                </a>
            </li>

            <!-- Profile Info -->
            <li>
                <a href="{{ route('profile.info') }}"
                   class="flex items-center px-4 py-3 rounded-md transition hover:bg-gradient-to-r hover:from-blue-500 hover:to-blue-600 hover:text-white group
                          {{ request()->routeIs('profile.info') ? 'bg-blue-600 text-white' : '' }}">
                    <i class="fas fa-user mr-3 text-blue-500 group-hover:text-white"></i>
                    <span class="sidebar-text">Profile Info</span>
                </a>
            </li>

            <!-- Update Password -->
            <li>
                <a href="{{ route('profile.password') }}"
                   class="flex items-center px-4 py-3 rounded-md transition hover:bg-gradient-to-r hover:from-green-500 hover:to-green-600 hover:text-white group
                          {{ request()->routeIs('profile.password') ? 'bg-green-600 text-white' : '' }}">
                    <i class="fas fa-key mr-3 text-green-500 group-hover:text-white"></i>
                    <span class="sidebar-text">Update Password</span>
                </a>
            </li>

            <!-- Delete Account -->
            <li>
                <a href="{{ route('profile.delete') }}"
                   class="flex items-center px-4 py-3 rounded-md transition hover:bg-gradient-to-r hover:from-red-500 hover:to-red-600 hover:text-white group
                          {{ request()->routeIs('profile.delete') ? 'bg-red-600 text-white' : '' }}">
                    <i class="fas fa-user-slash mr-3 text-red-500 group-hover:text-white"></i>
                    <span class="sidebar-text">Delete Account</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
<!-- sidebar overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden" aria-hidden="true"></div>