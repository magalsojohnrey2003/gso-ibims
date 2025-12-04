<aside id="sidebar"
    role="navigation"
    aria-label="Admin sidebar"
    class="w-64 sidebar is-expanded h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="admin"
    data-desktop-collapsed="false"
    aria-hidden="false">

    <!-- Navigation -->
    <nav class="mt-6" role="menu" aria-label="Admin main menu">
        <ul class="space-y-2" role="none">

            {{-- Dashboard --}}
            <li role="none">
                <a href="{{ route('admin.dashboard') }}"
                   title="Home"
                   role="menuitem"
                   @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('admin.dashboard') ? 'gov-active' : '' }}">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Home</span>
                </a>
            </li>

            {{-- Items --}}
            <li role="none">
                <a href="{{ route('items.index') }}"
                   title="Items"
                   role="menuitem"
                   @if(request()->routeIs('items.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('items.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-box-open mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Items Management</span>
                </a>
            </li>

            {{-- Walk-in Requests --}}
            <li role="none">
                <a href="{{ route('admin.walkin.index') }}"
                   title="Walk-in Requests"
                   role="menuitem"
                   @if(request()->routeIs('admin.walkin.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('admin.walkin.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-user-check mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Walk-in Requests</span>
                </a>
            </li>

            {{-- Borrow Requests --}}
            <li role="none">
                <a href="{{ route('borrow.requests') }}"
                   title="Borrow Requests"
                   role="menuitem"
                   @if(request()->routeIs('borrow.requests')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('borrow.requests') ? 'gov-active' : '' }}">
                    <i class="fas fa-handshake mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Borrow Requests</span>
                </a>
            </li>

            {{-- Manpower Requests (NEW) --}}
            <li role="none">
                <a href="{{ route('admin.manpower.requests.index') }}"
                   title="Manpower Requests"
                   role="menuitem"
                   @if(request()->routeIs('admin.manpower.requests.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('admin.manpower.requests.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-users mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Manpower Requests</span>
                </a>
            </li>

            {{-- Return Items --}}
            <li role="none">
                <a href="{{ route('admin.return-items.index') }}"
                   title="Return Items"
                   role="menuitem"
                   @if(request()->routeIs('admin.return-items.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('admin.return-items.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-undo mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Return Items</span>
                </a>
            </li>

            {{-- Manage Users --}}
            <li role="none">
                <a href="{{ route('admin.users.index') }}"
                   title="Manage Users"
                   role="menuitem"
                   @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('admin.users.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-users-cog mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Manage Users</span>
                </a>
            </li>

            {{-- Reports --}}
            <li role="none">
                <a href="{{ route('reports.index') }}"
                   title="Reports"
                   role="menuitem"
                   @if(request()->routeIs('reports.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('reports.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-chart-line mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
