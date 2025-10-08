// resources/js/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

/**
 * Echo/Reverb initialization
 * - Uses Vite env vars: VITE_REVERB_APP_KEY, VITE_REVERB_HOST, VITE_REVERB_PORT, VITE_REVERB_SCHEME
 * - Adds CSRF header for the auth endpoint so private channels can be authorized by Laravel.
 */
const REVERB_KEY = import.meta.env.VITE_REVERB_APP_KEY ?? '';
const REVERB_HOST = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const REVERB_PORT = import.meta.env.VITE_REVERB_PORT
  ? Number(import.meta.env.VITE_REVERB_PORT)
  : (window.location.protocol === 'https:' ? 443 : 80);
const REVERB_SCHEME = (import.meta.env.VITE_REVERB_SCHEME ?? window.location.protocol.replace(':', ''));
const FORCE_TLS = REVERB_SCHEME === 'https';

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: REVERB_KEY,
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: FORCE_TLS,
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
  authEndpoint: '/broadcasting/auth',
  auth: {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      'X-Requested-With': 'XMLHttpRequest'
    }
  }
});

/* -------------------------
   Helpers
   ------------------------- */
function readMeta(name) {
  const m = document.querySelector(`meta[name="${name}"]`);
  return m ? m.getAttribute('content') : null;
}

function escapeHtml(unsafe) {
  if (unsafe === null || unsafe === undefined) return '';
  return String(unsafe)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function buildFriendlyMessage(payload) {
  // Try to create a friendly fallback when `message` is not provided
  if (payload.message) return payload.message;
  if (payload.new_status && payload.id) {
    return `Request #${payload.id} status changed to ${payload.new_status}`;
  }
  if (payload.id && payload.user_name) {
    return `New activity for request #${payload.id}`;
  }
  return 'You have a new notification';
}

/* -------------------------
   DOM updates & toast
   ------------------------- */
function ensureBadgeExists() {
  let badge = document.getElementById('notificationBadge');
  if (badge) return badge;

  const btn = document.getElementById('notificationBtn');
  if (!btn) return null;

  // Ensure parent is positioned so absolute badge works
  if (getComputedStyle(btn).position === 'static') {
    btn.style.position = 'relative';
  }

  badge = document.createElement('span');
  badge.id = 'notificationBadge';
  badge.className = 'absolute -top-1 -right-1 inline-flex items-center justify-center rounded-full text-xs w-5 h-5 bg-red-600 text-white';
  badge.textContent = '0';
  btn.appendChild(badge);
  return badge;
}

function prependNotificationNode(payload) {
  const dropdown = document.getElementById('notificationDropdown');
  if (!dropdown) return;

  // Remove the placeholder empty text if present
  const placeholder = dropdown.querySelector('p');
  if (placeholder && /empty/i.test(placeholder.textContent || '')) {
    dropdown.innerHTML = '';
  }

  const anchor = document.createElement('a');
  anchor.setAttribute('role', 'menuitem');
  anchor.setAttribute('href', payload.url ?? '#');
  anchor.className = 'block py-2 px-2 border-b last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700';

  const title = escapeHtml(payload.user_name ?? (payload.title ?? 'Notification'));
  const message = escapeHtml(payload.message ?? buildFriendlyMessage(payload));
  const dates = payload.borrow_date ? `${escapeHtml(payload.borrow_date)} → ${escapeHtml(payload.return_date ?? '')}` : '';

  anchor.innerHTML = `
    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">${title}</div>
    <div class="text-xs text-gray-500 dark:text-gray-300 mt-0.5">${message}</div>
    ${dates ? `<div class="text-xs text-gray-400 mt-1">${dates}</div>` : ''}
  `;

  // Prepend so newest are on top
  dropdown.prepend(anchor);
}

/**
 * Main notification handler that:
 *  - normalizes payload
 *  - updates badge + dropdown
 *  - calls showToast() if available
 */
function handleIncomingNotification(raw) {
  try {
    const payload = (raw && raw.data) ? raw.data : raw || {};
    const message = payload.message ?? buildFriendlyMessage(payload);

    if (typeof window.refreshNotifications === 'function') {
      window.refreshNotifications().catch(()=>{});
    }

    const badge = ensureBadgeExists();
    if (badge) {
      badge.classList.remove('hidden');
      const current = parseInt(badge.textContent || '0', 10) || 0;
      badge.textContent = String(current + 1);
    }

    if (typeof showToast === 'function') {
      showToast(message, 'success');
    } else {
      console.info('Notification:', message);
    }
  } catch (err) {
    console.error('Notification handling error', err);
  }
}


/* -------------------------
   Subscribe when ready
   ------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  // Try meta tags first. Add these in head:
  // <meta name="user-id" content="{{ auth()->id() ?? '' }}">
  // <meta name="user-role" content="{{ auth()->user()->role ?? '' }}">
  const userId = readMeta('user-id') || null;
  const userRole = readMeta('user-role') || '';

  if (userId) {
    // personal private channel
    try {
      const ch = window.Echo.private(`user.${userId}`);
      ch.listen('.BorrowRequestSubmitted', handleIncomingNotification);
      ch.listen('BorrowRequestSubmitted', handleIncomingNotification);
      ch.listen('.BorrowRequestStatusUpdated', handleIncomingNotification);
      ch.listen('BorrowRequestStatusUpdated', handleIncomingNotification);
    } catch (e) {
      console.warn('Failed to subscribe to user channel:', e);
    }
  }

  // Admin channel
  if (String(userRole).toLowerCase() === 'admin') {
    try {
      const adminCh = window.Echo.private('admin');
      adminCh.listen('.BorrowRequestSubmitted', handleIncomingNotification);
      adminCh.listen('BorrowRequestSubmitted', handleIncomingNotification);
      adminCh.listen('.BorrowRequestStatusUpdated', handleIncomingNotification);
      adminCh.listen('BorrowRequestStatusUpdated', handleIncomingNotification);
    } catch (e) {
      console.warn('Failed to subscribe to admin channel:', e);
    }
  }
});
