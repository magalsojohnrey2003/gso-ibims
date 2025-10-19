<!-- resources/views/layouts/sidebar-user.blade.php -->
<aside id="sidebar"
    role="navigation"
    aria-label="User sidebar"
    class="w-64 sidebar h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="user"
    aria-hidden="true">

    <!-- Header -->
    <div class="flex items-center justify-between p-5">
        <a href="{{ route('user.dashboard') }}" class="flex items-center space-x-2 sidebar-logo" title="GSO-IBIMS home">
            <img src="{{ asset('images/logo2.png') }}" alt="GSO logo" class="h-8 w-8 object-contain logo-img">
            <span class="text-lg font-bold sidebar-text">GSO-IBIMS</span>
        </a>

        <button id="sidebarToggle"
                aria-label="Toggle sidebar"
                class="p-2 rounded-md transition hover:bg-white/10 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-offset-1">
            <i class="fas fa-bars text-lg" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-6" role="menu" aria-label="User main menu">
        <ul class="space-y-2" role="none">

            {{-- Home --}}
            <li role="none">
                <a href="{{ route('user.dashboard') }}"
                   title="Home"
                   role="menuitem"
                   @if(request()->routeIs('user.dashboard')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('user.dashboard') ? 'gov-active' : '' }}">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Home</span>
                </a>
            </li>

            {{-- Borrow Items --}}
            <li role="none">
                <a href="{{ route('borrow.items') }}"
                   title="Borrow Items"
                   role="menuitem"
                   @if(request()->routeIs('borrow.items')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('borrow.items') ? 'gov-active' : '' }}">
                    <i class="fas fa-cart-plus mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Borrow Items</span>
                </a>
            </li>

            {{-- My Borrowed Items --}}
            <li role="none">
                <a href="{{ route('my.borrowed.items') }}"
                   title="My Borrowed Items"
                   role="menuitem"
                   @if(request()->routeIs('my.borrowed.items')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('my.borrowed.items') ? 'gov-active' : '' }}">
                    <i class="fas fa-clipboard-list mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">My Borrowed Items</span>
                </a>
            </li>

            {{-- Return Items --}}
            <li role="none">
                <a href="{{ route('return.items') }}"
                   title="Return Items"
                   role="menuitem"
                   @if(request()->routeIs('return.items')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('return.items') ? 'gov-active' : '' }}">
                    <i class="fas fa-undo mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Return Items</span>
                </a>
            </li>
        </ul>
    </nav>

</aside>
<!-- sidebar overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden" aria-hidden="true"></div>