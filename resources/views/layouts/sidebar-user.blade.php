<aside id="sidebar"
    role="navigation"
    aria-label="User sidebar"
    class="w-64 sidebar h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="user"
    aria-hidden="true">

    <!-- Navigation -->
    <nav class="mt-6" role="menu" aria-label="User main menu">
        <ul class="space-y-2" role="none">

            {{-- Home --}}
            @unless(request()->routeIs('borrowList.*'))
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
            @endunless

            {{-- Borrow Items / Borrow List --}}
            <li role="none">
                <a href="{{ request()->routeIs('borrowList.*') ? route('borrowList.index') : route('borrow.items') }}"
                   title="{{ request()->routeIs('borrowList.*') ? 'Borrow List' : 'Borrow Items' }}"
                   role="menuitem"
                   @if(request()->routeIs('borrow.items') || request()->routeIs('borrowList.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ (request()->routeIs('borrow.items') || request()->routeIs('borrowList.*')) ? 'gov-active' : '' }}">
                    <i class="fas fa-cart-plus mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">{{ request()->routeIs('borrowList.*') ? 'Borrow List' : 'Borrow Items' }}</span>
                </a>
            </li>

            {{-- My Borrowed Items --}}
            @unless(request()->routeIs('borrowList.*'))
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
            @endunless

        </ul>
    </nav>
</aside>
