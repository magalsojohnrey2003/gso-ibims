<aside id="sidebar"
    role="navigation"
    aria-label="Admin sidebar"
    class="w-64 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-lg h-screen transition-all duration-300
           transform -translate-x-full lg:translate-x-0 lg:relative flex-shrink-0 sidebar"
    style="background: var(--sidebar-gradient); color: var(--nav-text);">

    <!-- Local CSS fallback for gov accent + hover behavior (polished for light/dark) -->
    <style>
        :root { --gov-accent: #A855F7; --gov-accent-rgba: 168,85,247; }

        /* Anchor baseline */
        .sidebar a { position: relative; }

        /* Hover container: keeps background & left accent stable (no vertical jitter) */
        .gov-hover {
          transition: background-color .15s ease, color .12s ease;
          /* ensure the anchor stacking context so ::before sits beneath content */
          z-index: 0;
        }

        /* subtle background overlay on hover (light mode) */
        .gov-hover:hover {
          background-color: rgba(var(--gov-accent-rgba), 0.04);
        }

        /* left accent bar (hidden by default; appears on hover) */
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

        /* show accent on hover (light mode) */
        .gov-hover:hover::before {
          background: rgba(var(--gov-accent-rgba), 0.18);
          opacity: 1;
        }

        /* Dark-mode: slightly stronger overlay & accent for contrast */
        .dark .gov-hover:hover {
          background-color: rgba(var(--gov-accent-rgba), 0.08);
        }
        .dark .gov-hover:hover::before {
          background: rgba(var(--gov-accent-rgba), 0.28);
          opacity: 1;
        }

        /* Active state uses solid gov accent but keep text/icon color unchanged */
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
        <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 sidebar-logo" title="GSO-IBIMS home">
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
    <nav class="mt-6" role="menu" aria-label="Admin main menu">
        <ul class="space-y-2" role="none">

            {{-- Dashboard --}}
            <li role="none">
                <a href="{{ route('admin.dashboard') }}"
                   title="Home"
                   role="menuitem"
                   @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('admin.dashboard') ? 'gov-active' : '' }}">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
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
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('items.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-box-open mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
                    <span class="sidebar-text">Items</span>
                </a>
            </li>
        
            {{-- Borrow Requests --}}
            <li role="none">
                <a href="{{ route('borrow.requests') }}"
                   title="Borrow Requests"
                   role="menuitem"
                   @if(request()->routeIs('borrow.requests')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('borrow.requests') ? 'gov-active' : '' }}">
                    <i class="fas fa-handshake mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
                    <span class="sidebar-text">Borrow Requests</span>
                </a>
            </li>

            {{-- Return Requests --}}
            <li role="none">
                <a href="{{ route('return.requests') }}"
                   title="Return Requests"
                   role="menuitem"
                   @if(request()->routeIs('return.requests')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('return.requests') ? 'gov-active' : '' }}">
                    <i class="fas fa-undo mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
                    <span class="sidebar-text">Return Requests</span>
                </a>
            </li>

            {{-- Reports --}}
            <li role="none">
                <a href="{{ route('reports.index') }}"
                   title="Reports"
                   role="menuitem"
                   @if(request()->routeIs('reports.*')) aria-current="page" @endif
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          text-slate-800 dark:text-slate-100 {{ request()->routeIs('reports.*') ? 'gov-active' : '' }}">
                    <i class="fas fa-chart-line mr-3 sidebar-icon" aria-hidden="true" style="color: inherit;"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
            </li>
        </ul>
    </nav>

</aside>
