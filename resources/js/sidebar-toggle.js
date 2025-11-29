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
  var DESKTOP_BREAKPOINT = '(min-width: 1024px)';
  var COLLAPSED_CLASS = 'is-collapsed';
  var EXPANDED_CLASS = 'is-expanded';
  var LOCALSTORAGE_KEY = 'gso.sidebar.desktopCollapsed';

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

  function isDesktopViewport() {
    if (typeof window.matchMedia === 'function') {
      return window.matchMedia(DESKTOP_BREAKPOINT).matches;
    }
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    return viewportWidth >= 1024;
  }

  function getStoredDesktopCollapsed() {
    if (!sidebar) return true;
    try {
      var stored = window.localStorage.getItem(LOCALSTORAGE_KEY);
      if (stored === 'true') return true;
      if (stored === 'false') return false;
    } catch (e) {
      // ignore storage errors
    }

    var attr = sidebar.getAttribute('data-desktop-collapsed');
    if (attr === 'false') return false;
    if (attr === 'true') return true;

    // Default: expanded on desktop (not collapsed)
    return false;
  }

  function storeDesktopCollapsed(collapsed) {
    if (!sidebar) return;
    sidebar.setAttribute('data-desktop-collapsed', collapsed ? 'true' : 'false');
    try {
      window.localStorage.setItem(LOCALSTORAGE_KEY, collapsed ? 'true' : 'false');
    } catch (e) {
      // ignore storage errors (e.g., disabled storage)
    }
  }

  function applyDesktopCollapsedState(collapsed) {
    if (!sidebar) return;
    storeDesktopCollapsed(collapsed);

    if (!isDesktopViewport()) {
      sidebar.classList.remove(COLLAPSED_CLASS, EXPANDED_CLASS);
      return;
    }

    if (collapsed) {
      sidebar.classList.add(COLLAPSED_CLASS);
      sidebar.classList.remove(EXPANDED_CLASS);
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    } else {
      sidebar.classList.add(EXPANDED_CLASS);
      sidebar.classList.remove(COLLAPSED_CLASS);
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
    }
  }

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

    if (isDesktopViewport()) {
      applyDesktopCollapsedState(!getStoredDesktopCollapsed());
      return;
    }

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
          if (!sidebar) return;
          var collapsed = getStoredDesktopCollapsed();
          applyDesktopCollapsedState(!collapsed);
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
      if (isDesktopViewport()) {
        // Desktop: show sidebar, hide overlay, remove scroll lock
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (overlay) overlay.classList.add('hidden');
        sidebar.setAttribute('aria-hidden', 'false');
        removeScrollLock();
        applyDesktopCollapsedState(getStoredDesktopCollapsed());
      } else {
        sidebar.classList.remove(COLLAPSED_CLASS, EXPANDED_CLASS);
        // Mobile/tablet: if not explicitly open, hide sidebar
        if (!isOpen()) {
          sidebar.classList.add('-translate-x-full');
          sidebar.classList.remove('translate-x-0');
          sidebar.setAttribute('aria-hidden', 'true');
          if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
        if (toggle) toggle.setAttribute('aria-expanded', isOpen() ? 'true' : 'false');
      }
    } catch (err) {
      console.error('[sidebar-toggle] handleResize error', err);
    }
  }

  function init() {
    bindToggleButton();
    bindOverlay();
    // Apply stored desktop collapsed state early to reduce layout flicker
    try {
      if (isDesktopViewport()) {
        applyDesktopCollapsedState(getStoredDesktopCollapsed());
      }
    } catch (e) {
      // fallback to full resize handler
    }

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