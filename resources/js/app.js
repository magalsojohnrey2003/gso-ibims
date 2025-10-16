import './bootstrap';
import './item-filepond';
import './echo';       
import './notifications';
import '@fortawesome/fontawesome-free/css/all.min.css';
import Alpine from 'alpinejs';
import './borrow-requests'; 
import './my-borrowed-items'; 
import './return-requests';
import './borrowList';
import './return-items';
import './admin-dashboard';
import './property-number';
import './items-add-modal';
import './items-edit-modal';
import '../css/admin-dashboard.css';
import './user-dashboard';
import './reports';
import './qty-controls';
import '../css/borrow-list.css';
import '../css/return-selected-items.css';
import './item-categories';
import './item-manual-entry'; 
import './item-instances-editor';

window.Alpine = Alpine;

Alpine.start();

// --- Real-time admin events helper ---
// Dispatches custom DOM events so individual modules can react (refresh, highlight rows, etc.)
document.addEventListener('DOMContentLoaded', () => {
  try {
    const role = document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '';
    if (String(role).toLowerCase() !== 'admin') return;
    if (!window.Echo) {
      console.warn('Echo not initialized yet.');
      return;
    }

    const adminChannel = window.Echo.private('admin');

    // New borrow request submitted
    adminChannel.listen('.BorrowRequestSubmitted', (payload) => {
      // payload may come wrapped in `.data` depending on server; normalize:
      const data = (payload && payload.data) ? payload.data : payload;
      window.dispatchEvent(new CustomEvent('realtime:borrow-request-submitted', { detail: data }));
    });

    // Borrow request status updated
    adminChannel.listen('.BorrowRequestStatusUpdated', (payload) => {
      const data = (payload && payload.data) ? payload.data : payload;
      window.dispatchEvent(new CustomEvent('realtime:borrow-request-status-updated', { detail: data }));
    });

  } catch (err) {
    console.error('Admin realtime wiring failed', err);
  }
});

// Robust Step 3 toggle: binds to all [data-step3-header] / [data-step3-body] pairs
document.addEventListener('DOMContentLoaded', () => {
  const headers = Array.from(document.querySelectorAll('[data-step3-header]'));

  if (!headers.length) return;

  const isFocusable = (el) => !!el && (el.tabIndex >= 0 || /^(INPUT|TEXTAREA|SELECT|BUTTON)$/.test(el.tagName));

  const findBodyForHeader = (header) => {
    // 1) If header has aria-controls -> use that id
    const ctrl = header.getAttribute('aria-controls');
    if (ctrl) {
      const byId = document.getElementById(ctrl);
      if (byId) return byId;
    }
    // 2) If header and body are inside the same wrapper, look for nearest sibling with data-step3-body
    //    (walk up to a reasonable parent and query inside)
    let parent = header.parentElement;
    for (let i = 0; i < 4 && parent; i++, parent = parent.parentElement) {
      const found = parent.querySelector('[data-step3-body]');
      if (found) return found;
    }
    // 3) fallback: nextElementSibling
    let next = header.nextElementSibling;
    while (next) {
      if (next.matches && next.matches('[data-step3-body]')) return next;
      next = next.nextElementSibling;
    }
    return null;
  };

  headers.forEach((header) => {
    // avoid double-binding
    if (header.__step3Bound) return;
    header.__step3Bound = true;

    const body = findBodyForHeader(header);
    const caret = header.querySelector('[data-step3-caret]');

    const openBody = () => {
      if (!body) return;
      header.setAttribute('aria-expanded', 'true');
      // set maxHeight so CSS transition animates
      body.style.maxHeight = body.scrollHeight + 'px';
      body.style.opacity = '1';
      if (caret) caret.style.transform = 'rotate(180deg)';
      // focus first focusable element inside the body
      const focusable = body.querySelector('input:not([type="hidden"]), textarea, select, button, [tabindex]:not([tabindex="-1"])');
      if (focusable) {
        try { focusable.focus({ preventScroll: true }); } catch (_) { focusable.focus(); }
      }
    };

    const closeBody = () => {
      if (!body) return;
      header.setAttribute('aria-expanded', 'false');
      body.style.maxHeight = '0';
      body.style.opacity = '0';
      if (caret) caret.style.transform = '';
    };

    // initialize collapsed if aria-expanded isn't true already
    const initialExpanded = header.getAttribute('aria-expanded') === 'true';
    if (body) {
      // ensure transition styles exist (non-destructive)
      body.style.overflow = 'hidden';
      body.style.transition = body.style.transition || 'max-height 300ms ease, opacity 200ms ease';
      if (initialExpanded) {
        // open with measured height
        body.style.maxHeight = body.scrollHeight + 'px';
        body.style.opacity = '1';
        if (caret) caret.style.transform = 'rotate(180deg)';
      } else {
        closeBody();
      }
    }

    // click handler
    header.addEventListener('click', (ev) => {
      ev.preventDefault();
      const expanded = header.getAttribute('aria-expanded') === 'true';
      if (expanded) closeBody();
      else openBody();
    });

    // keyboard handler (Enter / Space)
    header.addEventListener('keydown', (ev) => {
      if (ev.key === ' ' || ev.key === 'Enter') {
        ev.preventDefault();
        header.click();
      }
      // allow arrow keys to move focus between headers (optional nicety)
      if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
        ev.preventDefault();
        const idx = headers.indexOf(header);
        if (idx >= 0) {
          const nextIdx = ev.key === 'ArrowDown' ? Math.min(headers.length - 1, idx + 1) : Math.max(0, idx - 1);
          const nextHeader = headers[nextIdx];
          if (nextHeader) nextHeader.focus();
        }
      }
    });
  });
});






