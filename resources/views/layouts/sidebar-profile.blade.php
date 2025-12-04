<aside id="sidebar"
    role="navigation"
    aria-label="Profile sidebar"
    class="w-64 sidebar is-expanded h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="profile"
    data-desktop-collapsed="false"
    aria-hidden="false">

    <!-- Navigation -->
    <nav class="mt-6" role="menu" aria-label="Profile menu">
        <ul class="space-y-2" role="none">
            <!-- Back to Home -->
            <li role="none">
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('user.dashboard') }}"
                   role="menuitem"
                   title="Home"
                   @if(request()->routeIs('admin.dashboard') || request()->routeIs('user.dashboard')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('admin.dashboard') || request()->routeIs('user.dashboard') ? 'gov-active' : '' }}">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Home</span>
                </a>
            </li>

            <!-- Profile Info -->
            <li role="none">
                <a href="{{ route('profile.info') }}"
                   role="menuitem"
                   title="Profile Info"
                   @if(request()->routeIs('profile.info')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('profile.info') ? 'gov-active' : '' }}">
                    <i class="fas fa-user mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Profile Info</span>
                </a>
            </li>

            <!-- Update Password -->
            <li role="none">
                <a href="{{ route('profile.password') }}"
                   role="menuitem"
                   title="Update Password"
                   @if(request()->routeIs('profile.password')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('profile.password') ? 'gov-active' : '' }}">
                    <i class="fas fa-key mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Update Password</span>
                </a>
            </li>

            <!-- Delete Account -->
            <li role="none">
                <a href="{{ route('profile.delete') }}"
                   role="menuitem"
                   title="Delete Account"
                   @if(request()->routeIs('profile.delete')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          {{ request()->routeIs('profile.delete') ? 'gov-active' : '' }}">
                    <i class="fas fa-user-slash mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Delete Account</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
