// resources/js/sidebar-toggle.js
// Robust sidebar toggle for mobile & desktop. Defensive checks and consistent scroll-lock handling.

(function () {
  // Config / IDs - adjust if your markup uses different IDs
  var SIDEBAR_ID = 'sidebar';
  var OVERLAY_ID = 'sidebarOverlay';
  var TOGGLE_ID = 'sidebarToggle'; // the button/icon that opens/closes sidebar

  // Cached elements (will be null if missing)
  var sidebar = document.getElementById(SIDEBAR_ID);
  var overlay = document.getElementById(OVERLAY_ID);
  var toggle = document.getElementById(TOGGLE_ID);

  // Small guard to avoid double-click/tap flooding
  var lastToggleAt = 0;
  var TOGGLE_DEBOUNCE_MS = 200;

  // Helper: check we have required elements; log useful warning once if missing
  function warnMissing(el, name) {
    if (!el) {
      // Only warn in dev consoles where developers can see it
      if (typeof console !== 'undefined' && console.warn) {
        console.warn('[sidebar-toggle] Missing element:', name);
      }
    }
  }

  warnMissing(sidebar, SIDEBAR_ID);
  warnMissing(overlay, OVERLAY_ID);
  warnMissing(toggle, TOGGLE_ID);

  // Helper to know whether the sidebar class indicates open
  function isOpen() {
    // If sidebar not present, consider false
    if (!sidebar) return false;
    // We treat "-translate-x-full" as hidden; absence means open for our markup
    return !sidebar.classList.contains('-translate-x-full');
  }

  // Apply scroll lock: add to both html and body to be safe across browsers
  function applyScrollLock() {
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');
  }
  function removeScrollLock() {
    document.documentElement.classList.remove('overflow-hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Show sidebar (mobile)
  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
    sidebar.setAttribute('aria-hidden', 'false');

    if (overlay) overlay.classList.remove('hidden');

    if (toggle) toggle.setAttribute('aria-expanded', 'true');

    applyScrollLock();
  }

  // Hide sidebar (mobile)
  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('-translate-x-full');
    sidebar.classList.remove('translate-x-0');
    sidebar.setAttribute('aria-hidden', 'true');

    if (overlay) overlay.classList.add('hidden');

    if (toggle) toggle.setAttribute('aria-expanded', 'false');

    removeScrollLock();
  }

  // Toggle with debounce
  function toggleSidebar() {
    var now = Date.now();
    if (now - lastToggleAt < TOGGLE_DEBOUNCE_MS) return;
    lastToggleAt = now;

    if (isOpen()) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }

  // Overlay click should close the sidebar
  function bindOverlay() {
    if (!overlay) return;
    // Use a single handler (do not add multiple listeners if this function called multiple times)
    if (!overlay._sidebar_overlay_bound) {
      overlay._sidebar_overlay_bound = true;
      overlay.addEventListener('click', function (ev) {
        ev.preventDefault();
        closeSidebar();
      }, { passive: true });
    }
  }

  // Ensure toggle button binds click/touch handlers safely
  function bindToggleButton() {
    if (!toggle) return;
    if (!toggle._sidebar_toggle_bound) {
      toggle._sidebar_toggle_bound = true;
      toggle.addEventListener('click', function (ev) {
        ev.preventDefault();
        toggleSidebar();
      }, { passive: false });

      // Also handle touchstart to improve responsiveness on mobile
      toggle.addEventListener('touchstart', function (ev) {
        // Prevent both click and touch from firing twice
        ev.preventDefault();
        toggleSidebar();
      }, { passive: false });
    }
  }

  // Ensure we restore correct state on resize
  function handleResize() {
    try {
      if (!sidebar) return;
      if (window.matchMedia('(min-width: 1024px)').matches) {
        // Desktop/large: ensure sidebar visible and overlay hidden, and no scroll lock
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (overlay) overlay.classList.add('hidden');
        sidebar.setAttribute('aria-hidden', 'false');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        removeScrollLock();
      } else {
        // Mobile/tablet: if user hasn't explicitly opened it, keep it hidden by default
        if (!isOpen()) {
          sidebar.classList.add('-translate-x-full');
          sidebar.classList.remove('translate-x-0');
          sidebar.setAttribute('aria-hidden', 'true');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
      }
    } catch (err) {
      console.error('[sidebar-toggle] handleResize error', err);
    }
  }

  // Initialize bindings (safe to call multiple times)
  function init() {
    bindToggleButton();
    bindOverlay();
    // Ensure initial state is consistent with CSS/responsive classes
    handleResize();

    // Bind resize with a small debounce
    var resizeTimer = null;
    window.addEventListener('resize', function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(handleResize, 120);
    });

    // Expose a global for external scripts to open/close if needed (optional)
    try {
      window.GSO_sidebar = {
        open: openSidebar,
        close: closeSidebar,
        toggle: toggleSidebar,
        isOpen: isOpen
      };
    } catch (e) { /* ignore */ }
  }

  // Start when DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();