import './bootstrap';
import './toast';
import './item-filepond';
import './echo';       
import './notifications';
import '@fortawesome/fontawesome-free/css/all.min.css';
import Alpine from 'alpinejs';
import './borrow-requests'; 
import './my-borrowed-items'; 
import './admin-return-items';
import './admin-manpower-requests';
import './borrowList';
import './borrow-list-wizard';
import './admin-dashboard';
import './property-number';
import './items-add-modal';
import './item-instances-manager';
import './items-edit-modal';
import './items-print';
import './items-overview-sync';
import './items-overview-manager';
import '../css/admin-dashboard.css';
import '../css/ui.css';
import './user-dashboard';
import './user-manpower';
import './reports';
import './qty-controls';
import '../css/borrow-list.css';
import '../css/assign-manpower.css';
import './sidebar-toggle';
import './admin-walk-in-index.js';
import './admin-walk-in.js';

window.Alpine = Alpine;

Alpine.start();

const SpinnerManager = (() => {
  const attr = 'data-loading';

  const setBusy = (el, state) => {
    if (!el) return;
    const tag = (el.tagName || '').toLowerCase();
    if (state) {
      if (window.getComputedStyle) {
        const color = getComputedStyle(el).color;
        if (color) {
          el.style.setProperty('--spinner-foreground', color);
        }
      }
      el.setAttribute(attr, 'true');
      if (tag === 'button' || tag === 'input') {
        el.dataset.spinnerWasDisabled = el.disabled ? 'true' : 'false';
        el.disabled = true;
      } else {
        el.dataset.spinnerWasDisabled = el.getAttribute('aria-disabled') === 'true' ? 'true' : 'false';
        el.setAttribute('aria-disabled', 'true');
      }
    } else {
      el.removeAttribute(attr);
      el.style.removeProperty('--spinner-foreground');
      if ((tag === 'button' || tag === 'input') && el.dataset.spinnerWasDisabled !== 'true') {
        el.disabled = false;
      }
      if (tag !== 'button' && tag !== 'input' && el.dataset.spinnerWasDisabled !== 'true') {
        el.removeAttribute('aria-disabled');
      }
      delete el.dataset.spinnerWasDisabled;
    }
  };

  const toggle = (el, state) => {
    if (!el) return;
    if (state) {
      if (el.hasAttribute(attr)) return;
      setBusy(el, true);
    } else if (el.hasAttribute(attr)) {
      setBusy(el, false);
    }
  };

  const handleFormSubmit = (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const method = (form.getAttribute('method') || form.method || 'get').toLowerCase();
    if (method === 'get') return;

    const submitter = (event.submitter && event.submitter.matches?.('button, input'))
      ? event.submitter
      : form.querySelector('button[type="submit"], input[type="submit"]');

    if (!submitter) return;
    if (!submitter.hasAttribute('data-spinner')) {
      submitter.setAttribute('data-spinner', 'true');
    }
    if (submitter.hasAttribute(attr)) {
      event.preventDefault();
      return;
    }

    toggle(submitter, true);

    // Failsafe in case submission is aborted client-side
    setTimeout(() => {
      if (submitter.hasAttribute(attr)) {
        toggle(submitter, false);
      }
    }, 15000);
  };

  const initForms = () => {
    document.addEventListener('submit', handleFormSubmit, true);
  };

  const patchFetch = () => {
    if (typeof window.fetch !== 'function') return;
    const nativeFetch = window.fetch.bind(window);
    let pendingButton = null;
    let pendingTimer = null;

    document.addEventListener(
      'click',
      (event) => {
        const btn = event.target.closest?.('[data-spinner]');
        if (!btn) return;
        if (!btn.hasAttribute('data-spinner')) {
          btn.setAttribute('data-spinner', 'true');
        }
        pendingButton = btn;
        if (pendingTimer) clearTimeout(pendingTimer);
        pendingTimer = window.setTimeout(() => {
          pendingButton = null;
          pendingTimer = null;
        }, 1500);
      },
      true
    );

    window.fetch = async (...args) => {
      const btn = pendingButton;
      pendingButton = null;
      if (pendingTimer) {
        clearTimeout(pendingTimer);
        pendingTimer = null;
      }
      if (btn) toggle(btn, true);
      try {
        return await nativeFetch(...args);
      } finally {
        if (btn) toggle(btn, false);
      }
    };
  };

  return {
    toggle,
    initForms,
    patchFetch,
  };
})();

SpinnerManager.patchFetch();
document.addEventListener('DOMContentLoaded', () => {
  SpinnerManager.initForms();
});
window.SpinnerManager = SpinnerManager;

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

// Generic accordion groups (used by item modals)
document.addEventListener('DOMContentLoaded', () => {
  const groups = Array.from(document.querySelectorAll('[data-accordion-group]'));
  if (!groups.length) return;

  const findPanel = (trigger) => {
    const ctrl = trigger.getAttribute('data-accordion-target');
    if (ctrl) {
      const byId = document.getElementById(ctrl);
      if (byId) return byId;
    }
    const direct = trigger.nextElementSibling;
    if (direct && direct.matches?.('[data-accordion-panel]')) return direct;
    const parent = trigger.parentElement;
    if (parent) {
      const candidate = parent.querySelector('[data-accordion-panel]');
      if (candidate) return candidate;
    }
    return null;
  };

  const focusFirst = (panel) => {
    const focusable = panel?.querySelector?.('input:not([type="hidden"]), textarea, select, button, [tabindex]:not([tabindex="-1"])');
    if (!focusable) return;
    try { focusable.focus({ preventScroll: true }); } catch (_) { focusable.focus(); }
  };

  groups.forEach((group) => {
    const triggers = Array.from(group.querySelectorAll('[data-accordion-trigger]'));
    if (!triggers.length) return;

    const items = triggers.map((trigger) => {
      if (trigger.__accordionBound) return null;
      const panel = findPanel(trigger);
      if (!panel) return null;
      const caret = trigger.querySelector('[data-accordion-caret]');
      panel.style.overflow = 'hidden';
      panel.style.transition = panel.style.transition || 'max-height 300ms ease, opacity 200ms ease';
      return { trigger, panel, caret };
    }).filter(Boolean);

    if (!items.length) return;

    const closeItem = (item, { silent = false } = {}) => {
      const { trigger, panel, caret } = item;
      trigger.setAttribute('aria-expanded', 'false');
      const currentHeight = panel.scrollHeight;
      if (currentHeight > 0) {
        panel.style.maxHeight = `${currentHeight}px`;
        requestAnimationFrame(() => {
          panel.style.maxHeight = '0';
        });
      } else {
        panel.style.maxHeight = '0';
      }
      panel.style.opacity = '0';
      panel.dataset.accordionExpanded = 'false';
      panel.removeAttribute('data-accordion-open');
      if (panel.__accordionHeightHandler) {
        panel.removeEventListener('transitionend', panel.__accordionHeightHandler);
        panel.__accordionHeightHandler = null;
      }
      if (caret) caret.style.transform = '';
      if (!silent) {
        panel.dispatchEvent(new CustomEvent('accordion:closed', { bubbles: true }));
      }
    };

    const openItem = (item, { focus = true, silent = false } = {}) => {
      const { trigger, panel, caret } = item;
      trigger.setAttribute('aria-expanded', 'true');
      panel.dataset.accordionExpanded = 'true';
      panel.removeAttribute('data-accordion-open');
      const targetHeight = panel.scrollHeight;
      panel.style.maxHeight = targetHeight > 0 ? `${targetHeight}px` : '240px';
      panel.style.opacity = '1';
      if (caret) caret.style.transform = 'rotate(180deg)';
      if (panel.__accordionHeightHandler) {
        panel.removeEventListener('transitionend', panel.__accordionHeightHandler);
        panel.__accordionHeightHandler = null;
      }
      panel.__accordionHeightHandler = (event) => {
        if (event.propertyName === 'max-height' && panel.dataset.accordionExpanded === 'true') {
          panel.style.maxHeight = 'none';
          if (panel.__accordionHeightHandler) {
            panel.removeEventListener('transitionend', panel.__accordionHeightHandler);
            panel.__accordionHeightHandler = null;
          }
        }
      };
      panel.addEventListener('transitionend', panel.__accordionHeightHandler);
      if (focus) focusFirst(panel);
      if (!silent) {
        panel.dispatchEvent(new CustomEvent('accordion:opened', { bubbles: true }));
      }
    };

    items.forEach((item, index) => {
      const { trigger, panel } = item;
      trigger.__accordionBound = true;

      const defaultOpen = panel.hasAttribute('data-accordion-open') || trigger.getAttribute('aria-expanded') === 'true';
      if (defaultOpen) {
        openItem(item, { focus: false, silent: true });
      } else {
        closeItem(item, { silent: true });
      }

      const handleAccordionClick = (event) => {
        event.preventDefault();
        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        if (expanded) {
          closeItem(item);
        } else {
          items.forEach((other) => {
            if (other !== item) closeItem(other, { silent: true });
          });
          
          // Scroll to the accordion section smoothly
          const accordionItem = trigger.closest('[data-accordion-item]');
          if (accordionItem) {
            // Small delay to ensure smooth animation
            setTimeout(() => {
              accordionItem.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start',
                inline: 'nearest'
              });
            }, 50);
          }
          
          openItem(item);
        }
      };

      trigger.addEventListener('click', handleAccordionClick);
      
      // Make the entire accordion container clickable (except the panel itself)
      const accordionItem = trigger.closest('[data-accordion-item]');
      if (accordionItem && !accordionItem.__accordionContainerBound) {
        accordionItem.__accordionContainerBound = true;
        accordionItem.style.cursor = 'pointer';
        
        accordionItem.addEventListener('click', (event) => {
          // Don't trigger if clicking inside the panel or on interactive elements
          if (panel.contains(event.target) || 
              event.target.matches('input, textarea, select, button, a, [tabindex]:not([tabindex="-1"])') ||
              event.target.closest('input, textarea, select, button, a')) {
            return;
          }
          
          // Trigger the accordion
          handleAccordionClick(event);
        });
      }

      trigger.addEventListener('keydown', (event) => {
        if (event.key === ' ' || event.key === 'Enter') {
          event.preventDefault();
          trigger.click();
          return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          const direction = event.key === 'ArrowDown' ? 1 : -1;
          const nextIndex = (index + direction + items.length) % items.length;
          items[nextIndex].trigger.focus();
        }
      });
    });

    const initiallyOpen = items.filter((item) => item.panel.dataset.accordionExpanded === 'true');
    if (initiallyOpen.length > 1) {
      initiallyOpen.slice(1).forEach((item) => closeItem(item, { silent: true }));
    }
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const fields = Array.from(document.querySelectorAll('[data-currency-format="php"]'));
  if (!fields.length) return;

  const formatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });

  const parseValue = (value) => {
    const normalized = String(value ?? '').replace(/\D/g, '');
    if (normalized === '') return '';
    const number = parseInt(normalized, 10);
    return Number.isFinite(number) ? number : '';
  };

  const bindCurrencyField = (input) => {
    if (!(input instanceof HTMLInputElement) || input.dataset.currencyBound === '1') return;
    input.dataset.currencyBound = '1';

    const applyFormatted = () => {
      const raw = parseValue(input.dataset.currencyRaw ?? input.value);
      if (raw === '') {
        input.value = '';
        input.dataset.currencyRaw = '';
        return;
      }
      input.dataset.currencyRaw = String(raw);
      input.value = formatter.format(raw);
    };

    const handleInput = () => {
      const raw = parseValue(input.value);
      if (raw === '') {
        input.value = '';
        input.dataset.currencyRaw = '';
        return;
      }
      input.dataset.currencyRaw = String(raw);
      input.value = formatter.format(raw);
      requestAnimationFrame(() => {
        try {
          const len = input.value.length;
          input.setSelectionRange(len, len);
        } catch (_) {
          /* caret positioning may fail on some inputs */
        }
      });
    };

    input.addEventListener('focus', () => {
      if (input.value) {
        requestAnimationFrame(() => {
          try { input.select(); } catch (_) { /* no-op */ }
        });
      }
    });

    input.addEventListener('blur', () => applyFormatted());
    input.addEventListener('input', handleInput);

    const form = input.form;
    if (form) {
      form.addEventListener('submit', () => {
        const raw = parseValue(input.value);
        input.value = raw === '' ? '' : String(raw);
      });
      form.addEventListener('reset', () => {
        delete input.dataset.currencyRaw;
        setTimeout(() => applyFormatted(), 0);
      });
    }

    applyFormatted();
  };

  fields.forEach(bindCurrencyField);
});

document.addEventListener('DOMContentLoaded', () => {
  const termsMeta = document.querySelector('meta[name="terms-accepted"]');
  const acceptUrlMeta = document.querySelector('meta[name="terms-accept-url"]');
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const modal = document.getElementById('terms-modal');
  const acceptBtn = document.getElementById('accept-terms-btn');
  const declineBtn = document.getElementById('decline-terms-btn');
  const secondaryDeclineBtn = document.getElementById('terms-modal-cancel');
  const errorBox = document.getElementById('terms-modal-error');
  const links = Array.from(document.querySelectorAll('[data-requires-terms="true"]'));

  if (!termsMeta || !acceptUrlMeta || !modal || !acceptBtn || links.length === 0) {
    return;
  }

  let hasAcceptedTerms = termsMeta.content === 'true';
  const acceptTermsUrl = acceptUrlMeta.content;
  const csrfToken = csrfMeta?.content || '';
  let destinationUrl = null;

  if (!acceptTermsUrl || hasAcceptedTerms) {
    return;
  }

  const showModal = () => {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    document.body?.classList.add('overflow-hidden');
  };

  const hideModal = () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    document.body?.classList.remove('overflow-hidden');
    if (errorBox) {
      errorBox.classList.add('hidden');
      errorBox.textContent = '';
    }
  };

  links.forEach((link) => {
    if (!(link instanceof HTMLAnchorElement)) {
      return;
    }
    link.addEventListener('click', (event) => {
      if (hasAcceptedTerms) {
        return;
      }
      event.preventDefault();
      destinationUrl = link.href;
      showModal();
    });
  });

  const handleDecline = () => {
    destinationUrl = null;
    hideModal();
  };

  declineBtn?.addEventListener('click', handleDecline);
  secondaryDeclineBtn?.addEventListener('click', handleDecline);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      handleDecline();
    }
  });

  acceptBtn.addEventListener('click', async () => {
    if (acceptBtn.disabled) {
      return;
    }

    if (errorBox) {
      errorBox.classList.add('hidden');
      errorBox.textContent = '';
    }

    const originalText = acceptBtn.textContent;
    acceptBtn.disabled = true;
    acceptBtn.textContent = 'Saving...';

    try {
      const response = await fetch(acceptTermsUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({}),
      });

      if (!response.ok) {
        throw new Error('Failed to accept terms');
      }

      hasAcceptedTerms = true;
      termsMeta.content = 'true';
      const target = destinationUrl;
      hideModal();
      destinationUrl = null;

      if (target) {
        window.location.href = target;
      }
    } catch (error) {
      console.error(error);
      if (errorBox) {
        errorBox.textContent = 'Unable to save your acceptance. Please try again.';
        errorBox.classList.remove('hidden');
      }
    } finally {
      acceptBtn.disabled = false;
      acceptBtn.textContent = originalText || 'Accept & Continue';
    }
  });
});

(function () {
  const observedTbodies = new WeakSet();
  const pendingRefresh = new WeakMap();

  const isRowVisible = (row) => {
    if (!(row instanceof HTMLElement)) return false;
    if (row.hidden || row.classList.contains('hidden')) return false;
    const style = window.getComputedStyle(row);
    if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity || '1') === 0) {
      return row.getClientRects().length > 0;
    }
    return true;
  };

  const refreshTableState = (tbody) => {
    if (!(tbody instanceof HTMLElement)) return;
    const rows = Array.from(tbody.children).filter((child) => child instanceof HTMLElement);
    if (!rows.length) return;

    const stateRows = rows.filter((row) => row.dataset && row.dataset.tableState);
    if (!stateRows.length) return;

    const hasVisibleDataRow = rows.some((row) => !row.dataset?.tableState && isRowVisible(row));
    const hasVisibleLoadingRow = stateRows.some((row) => row.dataset.tableState === 'loading' && isRowVisible(row));

    stateRows.forEach((row) => {
      if (row.dataset.tableState === 'empty') {
        row.style.display = (hasVisibleDataRow || hasVisibleLoadingRow) ? 'none' : '';
      }
    });
  };

  const scheduleRefresh = (tbody) => {
    if (pendingRefresh.has(tbody)) return;
    const rafId = requestAnimationFrame(() => {
      pendingRefresh.delete(tbody);
      refreshTableState(tbody);
    });
    pendingRefresh.set(tbody, rafId);
  };

  const observeTbody = (tbody) => {
    if (observedTbodies.has(tbody)) return;
    if (!tbody.querySelector('[data-table-state]')) return;
    observedTbodies.add(tbody);

    const observer = new MutationObserver(() => scheduleRefresh(tbody));
    observer.observe(tbody, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'hidden', 'style'],
    });
    tbody.__tableStateObserver = observer;
    refreshTableState(tbody);
  };

  const scanTbodies = () => {
    document.querySelectorAll('tbody').forEach(observeTbody);
  };

  document.addEventListener('DOMContentLoaded', scanTbodies);
  if (document.readyState !== 'loading') {
    scanTbodies();
  }

  const rootObserver = new MutationObserver(() => scanTbodies());
  rootObserver.observe(document.body, {
    childList: true,
    subtree: true,
  });
})();
