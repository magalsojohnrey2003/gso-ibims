// resources/js/sidebar-toggle.js
// Robust sidebar toggle for mobile & desktop. Defensive checks and consistent scroll-lock handling.

(function () {
  var SIDEBAR_ID = 'sidebar';
  var OVERLAY_ID = 'sidebarOverlay';
  var TOGGLE_ID = 'sidebarToggle';

  var sidebar = document.getElementById(SIDEBAR_ID);
  var overlay = document.getElementById(OVERLAY_ID);
  var toggle = document.getElementById(TOGGLE_ID);

  var lastToggleAt = 0;
  var TOGGLE_DEBOUNCE_MS = 200;

  function warnMissing(el, name) {
    if (!el) {
      if (typeof console !== 'undefined' && console.warn) {
        console.warn('[sidebar-toggle] Missing element:', name);
      }
    }
  }

  warnMissing(sidebar, SIDEBAR_ID);
  warnMissing(overlay, OVERLAY_ID);
  warnMissing(toggle, TOGGLE_ID);

  function isOpen() {
    if (!sidebar) return false;
    return !sidebar.classList.contains('-translate-x-full');
  }

  function applyScrollLock() {
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');
  }
  function removeScrollLock() {
    document.documentElement.classList.remove('overflow-hidden');
    document.body.classList.remove('overflow-hidden');
  }

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
    sidebar.setAttribute('aria-hidden', 'false');

    if (overlay) overlay.classList.remove('hidden');

    if (toggle) toggle.setAttribute('aria-expanded', 'true');

    applyScrollLock();
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('-translate-x-full');
    sidebar.classList.remove('translate-x-0');
    sidebar.setAttribute('aria-hidden', 'true');

    if (overlay) overlay.classList.add('hidden');

    if (toggle) toggle.setAttribute('aria-expanded', 'false');

    removeScrollLock();
  }

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

  function bindOverlay() {
    if (!overlay) return;
    if (!overlay._sidebar_overlay_bound) {
      overlay._sidebar_overlay_bound = true;
      overlay.addEventListener('click', function (ev) {
        ev.preventDefault();
        closeSidebar();
      }, { passive: true });
    }
  }

  function bindToggleButton() {
    if (!toggle) return;
    if (!toggle._sidebar_toggle_bound) {
      toggle._sidebar_toggle_bound = true;
      toggle.addEventListener('click', function (ev) {
        ev.preventDefault();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        if (viewportWidth < 1024) {
          toggleSidebar();
        } else {
          // Desktop toggle: collapse/expand sidebar width and toggle text visibility
          if (!sidebar) return;
          if (sidebar.classList.contains('w-64')) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
          } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
          }
          document.querySelectorAll('.sidebar-text, .sidebar-logo .logo-img').forEach(el => {
            el.classList.toggle('hidden');
          });
        }
      }, { passive: false });

      toggle.addEventListener('touchstart', function (ev) {
        ev.preventDefault();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        if (viewportWidth < 1024) {
          toggleSidebar();
        }
      }, { passive: false });
    }
  }

  function handleResize() {
    try {
      if (!sidebar) return;
      if (window.matchMedia('(min-width: 1024px)').matches) {
        // Desktop: show sidebar, hide overlay, remove scroll lock
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (overlay) overlay.classList.add('hidden');
        sidebar.setAttribute('aria-hidden', 'false');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        removeScrollLock();

        // Ensure sidebar has default width expanded
        if (sidebar.classList.contains('w-20')) {
          sidebar.classList.remove('w-20');
          sidebar.classList.add('w-64');
          document.querySelectorAll('.sidebar-text, .sidebar-logo .logo-img').forEach(el => {
            el.classList.remove('hidden');
          });
        }
      } else {
        // Mobile/tablet: if not explicitly open, hide sidebar
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

  function init() {
    bindToggleButton();
    bindOverlay();
    handleResize();

    var resizeTimer = null;
    window.addEventListener('resize', function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(handleResize, 120);
    });

    try {
      window.GSO_sidebar = {
        open: openSidebar,
        close: closeSidebar,
        toggle: toggleSidebar,
        isOpen: isOpen
      };
    } catch (e) { }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();