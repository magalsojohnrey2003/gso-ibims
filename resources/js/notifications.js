// resources/js/notifications.js
// Requirements: `window.Echo` (your echo.js) and showToast() already present.

/* ---------- helpers ---------- */
// small helper: time-ago (dynamic, shows "Just now" for very recent)
function timeAgo(iso) {
  if (!iso) return '';

  // Convert the ISO string to a Date and normalize it to local timezone
  const utcDate = new Date(iso);
  const localDate = new Date(utcDate.getTime() + (utcDate.getTimezoneOffset() * 60000 * -1));

  const sec = Math.floor((Date.now() - localDate.getTime()) / 1000);

  if (sec < 10) return 'Just now';
  if (sec < 60) return `${sec}s ago`;
  if (sec < 3600) return `${Math.floor(sec / 60)}m ago`;
  if (sec < 86400) return `${Math.floor(sec / 3600)}h ago`;

  const days = Math.floor(sec / 86400);
  return days === 1 ? '1 day ago' : `${days} days ago`;
}


function escapeHtml(s){
  if (s === null || s === undefined) return '';
  return String(s)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'", '&#039;');
}

async function apiJson(url, method = 'GET', body = null) {
  const headers = {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    'Accept': 'application/json'
  };
  if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH' )) {
    headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : null, credentials: 'same-origin' });
  if (!res.ok) {
    const t = await res.text().catch(()=>null);
    throw new Error(t || `HTTP ${res.status}`);
  }
  return res.json().catch(()=>null);
}

const VIEWER_ROLE = (document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '').toLowerCase();
const VIEWER_IS_ADMIN = VIEWER_ROLE === 'admin';
let notificationRoutes = {};

function parseDate(input) {
  if (!input) return null;
  if (input instanceof Date) return Number.isNaN(input.getTime()) ? null : input;

  const trimmed = typeof input === 'string' ? input.trim() : '';
  if (trimmed === '') return null;

  let date = new Date(trimmed);
  if (Number.isNaN(date.getTime()) && /^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    date = new Date(`${trimmed}T00:00:00`);
  }

  if (Number.isNaN(date.getTime())) return null;
  return date;
}

function formatDateValue(date, { includeTime = false } = {}) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
  const options = includeTime
    ? { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
    : { month: 'short', day: 'numeric' };
  return date.toLocaleString(undefined, options);
}

function formatDateRange(start, end, { includeTime = false } = {}) {
  const left = parseDate(start);
  const right = parseDate(end);
  if (!left && !right) return '';
  if (left && !right) return formatDateValue(left, { includeTime });
  if (!left && right) return formatDateValue(right, { includeTime });

  const sameDay = left.toDateString() === right.toDateString();
  if (sameDay) {
    if (!includeTime) {
      return formatDateValue(left, { includeTime: false });
    }
    return `${formatDateValue(left, { includeTime: true })} – ${formatDateValue(right, { includeTime: true })}`;
  }

  return `${formatDateValue(left, { includeTime })} → ${formatDateValue(right, { includeTime })}`;
}

function formatBorrowStatusLabel(status) {
  if (!status) return '';
  const map = {
    pending: 'Pending Review',
    validated: 'Validated',
    approved: 'Approved',
    rejected: 'Rejected',
    returned: 'Returned',
    return_pending: 'Awaiting Return',
    qr_verified: 'QR Verified',
  };
  return map[status] || status.replaceAll('_', ' ').replace(/\b\w/g, ch => ch.toUpperCase());
}

function formatManpowerStatusLabel(status) {
  if (!status) return '';
  const map = {
    pending: 'Pending Review',
    validated: 'Validated',
    approved: 'Approved',
    rejected: 'Rejected',
  };
  return map[status] || status.replaceAll('_', ' ').replace(/\b\w/g, ch => ch.toUpperCase());
}

function summarizeItems(items) {
  if (!Array.isArray(items) || items.length === 0) return '';
  const first = items[0] ?? {};
  const qty = first.quantity ? `${first.quantity}× ` : '';
  const name = first.name || '';
  let summary = `${qty}${name}`.trim();
  if (items.length > 1) {
    summary += ` +${items.length - 1} more`;
  }
  return summary.trim();
}

function deriveInitials(name) {
  if (!name) return 'NA';
  const clean = String(name).trim();
  if (clean === '') return 'NA';
  const parts = clean.split(/\s+/).filter(Boolean);
  if (parts.length === 0) return clean.substring(0, 2).toUpperCase();
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function determineCategory(type) {
  if (!type) return 'Updates';
  if (type.startsWith('borrow') || type.startsWith('delivery')) return 'Borrow Items';
  if (type === 'manpower_submitted') return 'Manpower Requests';
  if (type === 'manpower_status_changed') return 'Request Manpower';
  if (type.startsWith('manpower')) return 'Manpower';
  if (type === 'user_registered') return 'Manage Users';
  if (type.startsWith('user_')) return 'User Accounts';
  return 'Updates';
}

function appendQuery(url, key, value) {
  if (!url || !key || value === undefined || value === null || value === '') return url;
  const separator = url.includes('?') ? '&' : '?';
  return `${url}${separator}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
}

function resolveNotificationTarget(notification) {
  const raw = notification?.data ?? notification ?? {};
  const type = String(raw.type || '').toLowerCase();

  if (raw.url) return raw.url;
  if (raw.link) return raw.link;

  if (type.startsWith('manpower') && notificationRoutes.manpower) {
    const identifier = raw.manpower_request_uuid || raw.manpower_request_id || raw.request_id;
    return appendQuery(notificationRoutes.manpower, 'highlight', identifier);
  }

  if (type.startsWith('borrow') && notificationRoutes.borrow) {
    const identifier = raw.borrow_request_uuid || raw.borrow_request_id || raw.request_id;
    return appendQuery(notificationRoutes.borrow, 'highlight', identifier);
  }

  if (type.startsWith('delivery') && notificationRoutes.returnItems) {
    return appendQuery(notificationRoutes.returnItems, 'highlight', raw.borrow_request_id || raw.request_id);
  }

  if (type.startsWith('user_') && notificationRoutes.users) {
    return notificationRoutes.users;
  }

  if (notificationRoutes.updates) {
    return notificationRoutes.updates;
  }

  return null;
}

function buildManpowerLocation(data) {
  const parts = [];
  if (data.location) parts.push(data.location);
  if (data.barangay) parts.push(data.barangay);
  if (data.municipality) parts.push(data.municipality);
  return parts.join(', ');
}

function shapeNotificationDisplay(notification) {
  const raw = notification?.data ?? notification ?? {};
  const type = String(raw.type || '').toLowerCase();
  const category = determineCategory(type);

  const actorName = raw.actor_name || '';
  const requesterName = raw.user_name || '';
  const formattedId = raw.formatted_request_id
    || (raw.borrow_request_id ? `Request #${raw.borrow_request_id}` : (raw.manpower_request_id ? `Request #${raw.manpower_request_id}` : ''));

  const display = {
    category,
    title: raw.message || 'Notification',
    description: '',
    metaLeft: category,
    badge: null,
    avatarText: deriveInitials(actorName || requesterName || category),
  };

  const addDescription = (...segments) => {
    const safe = segments.filter(Boolean).map(seg => String(seg).trim()).filter(Boolean);
    if (safe.length) {
      display.description = safe.join(' · ');
    }
  };

  switch (type) {
    case 'borrow_submitted': {
      const schedule = formatDateRange(raw.borrow_date, raw.return_date, { includeTime: false });
      const items = summarizeItems(raw.items);
      const pendingLabel = 'Pending Review';
      display.title = VIEWER_IS_ADMIN
        ? `${requesterName || 'Borrower'} submitted a borrow request`
        : 'Borrow request submitted';
      addDescription(
        formattedId,
        schedule ? `Schedule: ${schedule}` : '',
        items ? `Items: ${items}` : '',
        raw.purpose_office ? `Office: ${raw.purpose_office}` : ''
      );
      display.metaLeft = VIEWER_IS_ADMIN
        ? `${category} · ${requesterName || 'Borrower'}`
        : category;
      display.badge = pendingLabel;
      display.avatarText = deriveInitials(requesterName || actorName || category);
      break;
    }
    case 'borrow_status_changed': {
      const statusLabel = raw.status_label || formatBorrowStatusLabel(raw.new_status);
      const items = summarizeItems(raw.items);
      display.title = `${statusLabel || formatBorrowStatusLabel('updated')} — ${formattedId || 'Borrow Request'}`;
      addDescription(
        actorName ? `Handled by ${actorName}` : '',
        items ? `Items: ${items}` : '',
        raw.reason && raw.new_status === 'rejected' ? `Reason: ${raw.reason}` : ''
      );
      display.metaLeft = `${category} · ${statusLabel || 'Updated'}`;
      display.badge = statusLabel || null;
      display.avatarText = deriveInitials(actorName || requesterName || category);
      break;
    }
    case 'borrow_dispatched': {
      const schedule = formatDateRange(raw.borrow_date, raw.return_date, { includeTime: false });
      display.title = `Dispatched — ${formattedId || 'Borrow Request'}`;
      addDescription(
        actorName ? `Marked by ${actorName}` : '',
        schedule ? `Schedule: ${schedule}` : ''
      );
      display.metaLeft = `${category} · Dispatched`;
      display.badge = 'Dispatched';
      display.avatarText = deriveInitials(actorName || requesterName || category);
      break;
    }
    case 'borrow_delivered': {
      const when = raw.delivered_at ? formatDateRange(raw.delivered_at, raw.delivered_at, { includeTime: true }) : '';
      display.title = `Delivered — ${formattedId || 'Borrow Request'}`;
      addDescription(
        actorName ? `Marked by ${actorName}` : '',
        when ? `Delivered ${when}` : ''
      );
      display.metaLeft = `${category} · Delivered`;
      display.badge = 'Delivered';
      display.avatarText = deriveInitials(actorName || requesterName || category);
      break;
    }
    case 'borrow_dispatch_canceled': {
      display.title = `Dispatch canceled — ${formattedId || 'Borrow Request'}`;
      addDescription(actorName ? `Updated by ${actorName}` : '');
      display.metaLeft = `${category} · Canceled`;
      display.badge = 'Canceled';
      display.avatarText = deriveInitials(actorName || requesterName || category);
      break;
    }
    case 'delivery_confirmed': {
      display.title = `Delivery confirmed — ${formattedId || 'Borrow Request'}`;
      addDescription(requesterName ? `Confirmed by ${requesterName}` : '', actorName && actorName !== requesterName ? `Reviewed by ${actorName}` : '');
      display.metaLeft = `${category} · Delivery`;
      display.avatarText = deriveInitials(requesterName || actorName || category);
      break;
    }
    case 'delivery_reported': {
      display.title = `Delivery issue reported — ${formattedId || 'Borrow Request'}`;
      addDescription(
        requesterName ? `Reported by ${requesterName}` : '',
        raw.reason ? `Reason: ${raw.reason}` : ''
      );
      display.metaLeft = `${category} · Delivery`;
      display.badge = 'Attention';
      display.avatarText = deriveInitials(requesterName || actorName || category);
      break;
    }
    case 'manpower_submitted': {
      const schedule = formatDateRange(raw.start_at, raw.end_at, { includeTime: true });
      const location = buildManpowerLocation(raw);
      display.title = `${requesterName || 'Requester'} submitted a manpower request`;
      addDescription(
        formattedId,
        raw.role ? `Role: ${raw.role}` : '',
        raw.quantity ? `Qty: ${raw.quantity}` : '',
        schedule ? `Schedule: ${schedule}` : '',
        location ? `Location: ${location}` : ''
      );
      display.metaLeft = `${category} · Pending Review`;
      display.badge = 'Pending Review';
      display.avatarText = deriveInitials(requesterName || category);
      break;
    }
    case 'manpower_status_changed': {
      const statusLabel = raw.status_label || formatManpowerStatusLabel(raw.status);
      const schedule = formatDateRange(raw.start_at, raw.end_at, { includeTime: true });
      const location = buildManpowerLocation(raw);
      display.title = `${statusLabel || 'Updated'} — ${formattedId || 'Manpower Request'}`;
      addDescription(
        actorName ? `Handled by ${actorName}` : '',
        raw.role ? `Role: ${raw.role}` : '',
        raw.approved_quantity ? `Approved Qty: ${raw.approved_quantity}` : '',
        schedule ? `Schedule: ${schedule}` : '',
        location ? `Location: ${location}` : '',
        raw.rejection_reason_subject && raw.status === 'rejected' ? `Reason: ${raw.rejection_reason_subject}` : ''
      );
      display.metaLeft = `${category} · ${statusLabel || 'Updated'}`;
      display.badge = statusLabel || null;
      display.avatarText = deriveInitials(actorName || requesterName || category);
      break;
    }
    case 'user_registered': {
      display.title = `${requesterName || 'New user'} registered`;
      addDescription(
        raw.email ? `Email: ${raw.email}` : '',
        raw.creation_source ? `Source: ${raw.creation_source}` : ''
      );
      display.metaLeft = `${category} · Registration`;
      display.badge = 'New';
      display.avatarText = deriveInitials(requesterName || raw.email || category);
      break;
    }
    default: {
      display.title = raw.message || 'Notification';
      display.metaLeft = category;
      display.avatarText = deriveInitials(actorName || requesterName || category);
      break;
    }
  }

  return display;
}

/* ---------- rendering ---------- */
function getBadge() {
  return document.getElementById('notificationBadge');
}

function updateBadge(count) {
  const badge = getBadge();
  if (!badge) return;
  if (!count || count <= 0) {
    badge.classList.add('hidden');
    badge.textContent = '0';
  } else {
    badge.classList.remove('hidden');
    badge.textContent = String(count);
  }
}

/* -------------------- helper: update relative timestamps -------------------- */
function refreshTimeStamps() {
  document.querySelectorAll('.notif-time-rel').forEach(el => {
    const iso = el.dataset.notifCreated;
    if (!iso) return;
    el.textContent = timeAgo(iso);
  });
}


// ensure timestamps update frequently
let _notif_ts_interval;
function ensureTimestampUpdater() {
  if (_notif_ts_interval) return;
  // update every 15s so "Just now" -> "Xs ago" appears promptly
  _notif_ts_interval = setInterval(refreshTimeStamps, 15 * 1000);
}

/* -------------------- createNotificationCard (unified markup) -------------------- */
function createNotificationCard(notification) {
  // support both server notification object and raw payload
  const data = notification.data ?? notification;
  const read = !!notification.read_at;

  // wrapper button
  const wrapper = document.createElement('button');
  wrapper.type = 'button';
  // reserve left padding for stripe + maintain consistent layout for all items
  wrapper.className = 'relative w-full text-left py-3 pr-4 hover:bg-gray-50 dark:hover:bg-gray-800 flex gap-3 items-start transition pl-5';
  // dataset id (if available)
  wrapper.dataset.notificationId = notification.id ?? (notification.data && notification.data.id) ?? '';
  // created timestamp for live time updates
  wrapper.dataset.notifCreated = notification.created_at ?? data.created_at ?? new Date().toISOString();

  // background colors: unread = white, read = light gray
  if (read) {
    wrapper.classList.add('bg-gray-50', 'dark:bg-gray-900'); // read → subtle gray
  } else {
    wrapper.classList.add('bg-white', 'dark:bg-gray-800'); // unread → white
  }

  // left accent stripe for unread (absolute so it doesn't change flow)
  if (!read) {
    const accent = document.createElement('div');
    accent.className = 'absolute left-0 top-0 h-full w-1.5 bg-purple-500 rounded-r';
    wrapper.appendChild(accent);
  }

  const display = shapeNotificationDisplay(notification);

  // Avatar (initials fallback)
  const avatar = document.createElement('div');
  avatar.className = 'flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-sm font-semibold text-gray-700 dark:text-gray-200 border';
  avatar.textContent = display.avatarText || 'NA';

  // Body
  const body = document.createElement('div');
  body.className = 'flex-1';

  // Title/message (fully visible inline — no modal needed)
  const title = document.createElement('div');
  title.className = 'text-sm font-semibold text-gray-900 dark:text-gray-100';
  title.textContent = display.title || data.message || 'Notification';

  if (display.badge) {
    const badge = document.createElement('span');
    badge.className = 'ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-100 text-purple-700';
    badge.textContent = display.badge;
    title.appendChild(badge);
  }

  const meta = document.createElement('div');
  meta.className = 'text-xs text-gray-500 dark:text-gray-300 mt-1 flex items-center justify-between';

  const leftMeta = document.createElement('div');
  leftMeta.textContent = display.metaLeft || display.category || '';

    const rightMeta = document.createElement('div');
  rightMeta.className = 'flex items-center';

  // relative time only (updates)
  const timeRel = document.createElement('span');
  timeRel.className = 'notif-time-rel text-xs text-gray-400';
  timeRel.dataset.notifCreated = wrapper.dataset.notifCreated;
  timeRel.textContent = timeAgo(wrapper.dataset.notifCreated);

  rightMeta.appendChild(timeRel);


  meta.appendChild(leftMeta);
  meta.appendChild(rightMeta);

  body.appendChild(title);
  if (display.description) {
    // ensure description appears between title and meta
    const desc = document.createElement('div');
    desc.className = 'text-xs text-gray-600 dark:text-gray-300 mt-1';
    desc.textContent = display.description;
    body.appendChild(desc);
  }
  body.appendChild(meta);

  wrapper.appendChild(avatar);
  wrapper.appendChild(body);

  // click: mark read then navigate when applicable
  wrapper.addEventListener('click', async () => {

    const targetUrl = resolveNotificationTarget(notification);

    // optimistic visual update to 'read' state
    if (!wrapper.classList.contains('bg-gray-50')) {
      wrapper.classList.remove('bg-white', 'dark:bg-gray-800');
      wrapper.classList.add('bg-gray-50', 'dark:bg-gray-900');
      const stripe = wrapper.querySelector('.bg-purple-500');
      if (stripe) stripe.remove();
    }

    // if we have a DB notification id, call API to mark read
    if (wrapper.dataset.notificationId) {
      try {
        await apiJson(`/notifications/${wrapper.dataset.notificationId}/read`, 'POST');
      } catch (e) {
        console.error('Failed to mark read', e);
      }
    }

    if (targetUrl) {
      const dropdown = document.getElementById('notificationDropdown');
      const trigger = document.getElementById('notificationBtn');
      if (dropdown) dropdown.classList.add('hidden');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
      window.location.href = targetUrl;
      return;
    }

    if (typeof showToast === 'function') {
      try { showToast(data.message ?? 'Notification', 'success'); } catch(e){ /* ignore */ }
    }

    // re-fetch to ensure server is authoritative (will also reorder)
    try { await refreshNotifications(); } catch(e){ /* ignore */ }
  });

  // small read class
  if (read) wrapper.classList.add('read'); else wrapper.classList.remove('read');

  return wrapper;
}

/* -------------------- refreshNotifications + cache + load-more rendering -------------------- */
window.__notif_cache = window.__notif_cache || [];
window.__notif_show = window.__notif_show || 5;

function renderNotificationsFromCache(count) {
  const listEl = document.getElementById('notificationList');
  if (!listEl) return;
  listEl.innerHTML = '';

  if (!Array.isArray(window.__notif_cache) || window.__notif_cache.length === 0) {
    listEl.innerHTML = `<p class="px-4 py-4 text-sm text-gray-500 dark:text-gray-300 m-0">No notifications yet</p>`;
    updateBadge(0);
    return;
  }

  const toShow = window.__notif_cache.slice(0, count);
  toShow.forEach(n => {
    const node = (typeof createNotificationCard === 'function') ? createNotificationCard(n) : (function(n){
      const el = document.createElement('div');
      el.className = 'px-4 py-3 border-b border-gray-200 dark:border-slate-700';
      el.textContent = n.data?.message ?? n.message ?? 'Notification';
      return el;
    })(n);
    listEl.appendChild(node);
  });
}

async function refreshNotifications() {
  try {
    const notifications = await apiJson('/notifications/list', 'GET') || [];

    // sort unread first, then newest -> oldest
    notifications.sort((a, b) => {
      const aUnread = !a.read_at;
      const bUnread = !b.read_at;
      if (aUnread !== bUnread) return aUnread ? -1 : 1;
      return new Date(b.created_at) - new Date(a.created_at);
    });

    // cache and render first chunk
    window.__notif_cache = notifications;
    // keep load-more behaviour: show first X (default 5)
    window.__notif_show = window.__notif_show || 5;
    renderNotificationsFromCache(window.__notif_show);

    // update badge
    const unreadCount = notifications.filter(n => !n.read_at).length;
    updateBadge(unreadCount);

    // ensure timestamps update
    refreshTimeStamps();
    ensureTimestampUpdater();

    return notifications;
  } catch (err) {
    console.error('Failed to refresh notifications', err);
    return [];
  }
}

// expose refresh to other scripts (echo.js can call this)
window.refreshNotifications = refreshNotifications;

/* -------------------- mark all read (optimistic + refresh) -------------------- */
async function markAllRead() {
  try {
    // optimistic UI: convert all visible items into 'read' look
    document.querySelectorAll('#notificationList [data-notification-id]').forEach(el => {
      el.classList.remove('bg-white', 'dark:bg-gray-800');
      el.classList.add('bg-gray-50', 'dark:bg-gray-900');
      const stripe = el.querySelector('.bg-purple-500');
      if (stripe) stripe.remove();
    });
    updateBadge(0);

    await apiJson('/notifications/read-all', 'POST');
    await refreshNotifications();
  } catch (e) {
    console.error('Failed mark all read', e);
  }
}

// expose markAllRead if other code expects it
window.markAllRead = markAllRead;

/* ---------- realtime integration ---------- */
function bindRealtime() {
  try {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || null;
    const userRole = (document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '').toLowerCase();

    // personal channel
    if (userId && window.Echo && window.Echo.private) {
      window.Echo.private(`user.${userId}`)
        .listen('.request.notification', (payload) => {
          const p = (payload && payload.data) ? payload.data : payload;
          if (typeof showToast === 'function') showToast(p.message ?? 'Notification', (p.type && p.type.includes('rejected')) ? 'error' : 'success');
          // refresh list so dropdown shows message content immediately
          if (typeof refreshNotifications === 'function') refreshNotifications().catch(()=>{});
        });
    }

    // admin channel
    if (userRole === 'admin' && window.Echo && window.Echo.private) {
      window.Echo.private('admin')
        .listen('.request.notification', (payload) => {
          const p = (payload && payload.data) ? payload.data : payload;
          if (typeof showToast === 'function') showToast(p.message ?? 'Notification', 'success');
          if (typeof refreshNotifications === 'function') refreshNotifications().catch(()=>{});
        });
    }
  } catch (err) {
    console.error('Realtime bind failed', err);
  }
}

/* ---------- boot ---------- */
document.addEventListener('DOMContentLoaded', () => {
  // wire buttons
  const markAllBtn = document.getElementById('markAllReadBtn');
  if (markAllBtn) markAllBtn.addEventListener('click', markAllRead);

  const listEl = document.getElementById('notificationList');
  if (listEl) {
    notificationRoutes = {
      borrow: listEl.dataset.routeBorrow || null,
      manpower: listEl.dataset.routeManpower || null,
      returnItems: listEl.dataset.routeReturnItems || null,
      users: listEl.dataset.routeUsers || null,
      updates: listEl.dataset.routeUpdates || null,
    };
  }

  // Note: modal behavior removed — we do not open the modal on click anymore.
  // We therefore don't attach modal close/backdrop listeners.

  // Load more button handler (kept and visible)
  const loadMoreBtn = document.getElementById('loadMoreNotifications');
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', () => {
      window.__notif_show = (window.__notif_show || 5) + 5;
      renderNotificationsFromCache(window.__notif_show);
    });
  }

  // When the user clicks the bell, refresh the list so messages appear immediately in the dropdown
  const notifBtn = document.getElementById('notificationBtn');
  if (notifBtn) {
    notifBtn.addEventListener('click', () => {
      setTimeout(() => {
        if (typeof refreshNotifications === 'function') refreshNotifications().catch(()=>{});
      }, 60);
    });
  }

  // load initial set
  refreshNotifications();

  // bind realtime (echo)
  bindRealtime();
});
