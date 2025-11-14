<aside id="sidebar"
    role="navigation"
    aria-label="User sidebar"
    class="w-64 sidebar h-screen fixed inset-y-0 left-0 transition-all duration-300 transform -translate-x-full lg:translate-x-0 lg:relative z-40 flex-shrink-0"
    data-sidebar-type="user"
    aria-hidden="true">

    <!-- Navigation -->
    <nav class="mt-6" role="menu" aria-label="User main menu">
        <ul class="space-y-2" role="none">

            
            <li role="none">
                <a href="<?php echo e(route('user.dashboard')); ?>"
                   title="Home"
                   role="menuitem"
                   <?php if(request()->routeIs('user.dashboard')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('user.dashboard') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-home mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Home</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(request()->routeIs('borrowList.*') ? route('borrowList.index') : route('borrow.items')); ?>"
                   title="<?php echo e(request()->routeIs('borrowList.*') ? 'Borrow List' : 'Borrow Items'); ?>"
                   role="menuitem"
                   <?php if(request()->routeIs('borrow.items') || request()->routeIs('borrowList.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e((request()->routeIs('borrow.items') || request()->routeIs('borrowList.*')) ? 'gov-active' : ''); ?>"
                    data-requires-terms="true">
                    <i class="fas fa-cart-plus mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text"><?php echo e(request()->routeIs('borrowList.*') ? 'Borrow List' : 'Borrow Items'); ?></span>
                </a>
            </li>

            
            <?php if (! (request()->routeIs('borrowList.*'))): ?>
            <li role="none">
                <a href="<?php echo e(route('my.borrowed.items')); ?>"
                   title="My Borrowed Items"
                   role="menuitem"
                   <?php if(request()->routeIs('my.borrowed.items')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('my.borrowed.items') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-clipboard-list mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">My Borrowed Items</span>
                </a>
            </li>
            <?php endif; ?>

            
            <li role="none">
                <a href="<?php echo e(route('user.manpower.index')); ?>"
                   title="Request Manpower"
                   role="menuitem"
                   <?php if(request()->routeIs('user.manpower.*')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('user.manpower.*') ? 'gov-active' : ''); ?>"
                    data-requires-terms="true">
                    <i class="fas fa-people-carry mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Request Manpower</span>
                </a>
            </li>

            
            <li role="none">
                <a href="<?php echo e(route('user.terms')); ?>"
                   title="Terms &amp; Conditions"
                   role="menuitem"
                   <?php if(request()->routeIs('user.terms')): ?> aria-current="page" <?php endif; ?>
                   class="flex items-center px-4 py-3 rounded-md gov-hover transition-colors duration-150 group focus:outline-none focus:ring-2 focus:ring-offset-1
                          <?php echo e(request()->routeIs('user.terms') ? 'gov-active' : ''); ?>">
                    <i class="fas fa-shield-alt mr-3 sidebar-icon" aria-hidden="true"></i>
                    <span class="sidebar-text">Terms &amp; Conditions</span>
                </a>
            </li>

        </ul>
    </nav>
</aside>
<?php /**PATH C:\Users\magal\Desktop\gso-ibims\resources\views/layouts/sidebar-user.blade.php ENDPATH**/ ?>