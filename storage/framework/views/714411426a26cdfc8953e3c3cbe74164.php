<aside id="sidebar"
    role="navigation"
    aria-label="Admin sidebar"
    class="w-64 sidebar is-collapsed h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="admin"
    data-desktop-collapsed="true"
    aria-hidden="true">

    <!-- Navigation -->
    <nav class="mt-6" role="menu" aria-label="Admin main menu">
        <ul class="space-y-2" role="none">

            
            <li role="none">
                <a href="<?php echo e(route('admin.dashboard')); ?>"
                   title="Home"
                   role="menuitem"
                   <?php if(request()->routeIs('admin.dashboard')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('admin.dashboard') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Home</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('items.index')); ?>"
                   title="Items"
                   role="menuitem"
                   <?php if(request()->routeIs('items.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('items.*') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-box-open mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Items Management</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('admin.walkin.index')); ?>"
                   title="Walk-in Requests"
                   role="menuitem"
                   <?php if(request()->routeIs('admin.walkin.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('admin.walkin.*') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-user-check mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Walk-in Requests</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('borrow.requests')); ?>"
                   title="Borrow Requests"
                   role="menuitem"
                   <?php if(request()->routeIs('borrow.requests')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('borrow.requests') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-handshake mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Borrow Requests</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('admin.manpower.requests.index')); ?>"
                   title="Manpower Requests"
                   role="menuitem"
                   <?php if(request()->routeIs('admin.manpower.requests.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('admin.manpower.requests.*') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-users mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Manpower Requests</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('admin.return-items.index')); ?>"
                   title="Return Items"
                   role="menuitem"
                   <?php if(request()->routeIs('admin.return-items.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('admin.return-items.*') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-undo mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Return Items</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('admin.users.index')); ?>"
                   title="Manage Users"
                   role="menuitem"
                   <?php if(request()->routeIs('admin.users.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('admin.users.*') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-users-cog mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Manage Users</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('reports.index')); ?>"
                   title="Reports"
                   role="menuitem"
                   <?php if(request()->routeIs('reports.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('reports.*') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-chart-line mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
<?php /**PATH /home/u928333042/domains/gsoibims-tagoloan.com/public_html/resources/views/layouts/sidebar-admin.blade.php ENDPATH**/ ?>