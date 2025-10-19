// resources/js/sidebar-toggle.js
(function () {
  // IDs used in layouts: #sidebar, #sidebarToggle, #sidebarOverlay
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');

  if (!sidebar || !toggle || !overlay) {
    // If any element missing, do nothing
    return;
  }

  // Helper to open/close
  function isOpen() {
    return !sidebar.classList.contains('-translate-x-full');
  }

  function openSidebar() {
    // show sidebar
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
    sidebar.setAttribute('aria-hidden', 'false');
    // show overlay
    overlay.classList.remove('hidden');
    // update toggle aria
    toggle.setAttribute('aria-expanded', 'true');
    // prevent body scroll on mobile when sidebar open
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    sidebar.classList.remove('translate-x-0');
    sidebar.setAttribute('aria-hidden', 'true');
    overlay.classList.add('hidden');
    toggle.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('overflow-hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Initialize aria on toggle
  toggle.setAttribute('aria-controls', 'sidebar');
  toggle.setAttribute('aria-expanded', isOpen() ? 'true' : 'false');

  // Clicking the toggle toggles sidebar
  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    if (isOpen()) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  // Clicking overlay closes
  overlay.addEventListener('click', () => closeSidebar());

  // Close on Escape key
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && isOpen()) {
      closeSidebar();
    }
  });

  // Ensure that on resize to lg and above the overlay is hidden and body scroll allowed.
  function handleResize() {
    if (window.matchMedia('(min-width: 1024px)').matches) {
      // large screens: ensure classes set for visible sidebar, no overlay
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
      overlay.classList.add('hidden');
      sidebar.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
      toggle.setAttribute('aria-expanded', 'true');
    } else {
      // small screens: ensure sidebar starts hidden (unless user opened it)
      if (!isOpen()) {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        sidebar.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
      }
    }
  }

  window.addEventListener('resize', handleResize);
  // call once to set initial state
  handleResize();
})();