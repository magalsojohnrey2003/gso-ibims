<aside id="sidebar"
    role="navigation"
    aria-label="User sidebar"
    class="w-64 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-lg h-screen transition-all duration-300
           transform -translate-x-full lg:translate-x-0 lg:relative flex-shrink-0 sidebar"
    style="background: var(--sidebar-gradient); color: var(--nav-text);">

    <!-- Local CSS fallback for gov accent + hover behavior (polished for light/dark) -->
    <style>
        :root { --gov-accent: #A855F7; --gov-accent-rgba: 168,85,247; }

        .sidebar a { position: relative; }

        .gov-hover {
          transition: background-color .15s ease, color .12s ease;
          z-index: 0;
        }

        .gov-hover:hover {
          background-color: rgba(var(--gov-accent-rgba), 0.04);
        }

        .gov-hover::before {
          content: '';
          position: absolute;
          left: 0;
          top: 0;
          bottom: 0;
          width: 3px;
          border-top-right-radius: 4px;
          border-bottom-right-radius: 4px;
          background: transparent;
          opacity: 0;
          transition: background .15s ease, opacity .15s ease, transform .15s ease;
          pointer-events: none;
        }

        .gov-hover:hover::before {
          background: rgba(var(--gov-accent-rgba), 0.18);
          opacity: 1;
        }

        .dark .gov-hover:hover {
          background-color: rgba(var(--gov-accent-rgba), 0.08);
        }
        .dark .gov-hover:hover::before {
          background: rgba(var(--gov-accent-rgba), 0.28);
          opacity: 1;
        }

        .gov-active {
          background: var(--gov-accent) !important;
        }
        .gov-active .sidebar-text,
        .gov-active .sidebar-icon {
          color: inherit !important;
        }
    </style>

    <!-- Header -->
    <div class="flex items-center justify-between p-5">
        <a href="{{ route('user.dashboard') }}" class="flex items-center space-x-2 sidebar-logo" title="GSO-IBIMS home">
            <img src="{{ asset('images/logo2.png') }}" alt="GSO logo" class="h-8 w-8 object-contain logo-img">
            <span class="text-lg font-bold sidebar-text text-slate-800 dark:text-slate-100">GSO-IBIMS</span>
        </a>

        <button id="sidebarToggle"
                aria-label="Toggle sidebar"
                class="p-2 rounded-md transition hover:bg-white/10 dark:hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-offset-1">
            <i class="fas fa-bars text-lg" aria-hidden="true" style="color: inherit;"></i>
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
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('user.dashboard') ? 'gov-active' : '' }}">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
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
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('borrow.items') ? 'gov-active' : '' }}">
                    <i class="fas fa-cart-plus mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
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
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('my.borrowed.items') ? 'gov-active' : '' }}">
                    <i class="fas fa-clipboard-list mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
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
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('return.items') ? 'gov-active' : '' }}">
                    <i class="fas fa-undo mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
                    <span class="sidebar-text">Return Items</span>
                </a>
            </li>
        </ul>
    </nav>

</aside>
