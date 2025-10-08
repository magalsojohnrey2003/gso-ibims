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

  // Avatar (initials fallback)
  const avatar = document.createElement('div');
  avatar.className = 'flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-sm font-semibold text-gray-700 dark:text-gray-200 border';
  const initials = (data.user_name || '').split(' ').map(s => s.charAt(0)).join('').slice(0,2).toUpperCase() || 'U';
  avatar.textContent = initials;

  // Body
  const body = document.createElement('div');
  body.className = 'flex-1';

  // Title/message (fully visible inline — no modal needed)
  const title = document.createElement('div');
  title.className = 'text-sm font-medium text-gray-900 dark:text-gray-100';
  title.textContent = data.message ?? 'Notification';

  const meta = document.createElement('div');
  meta.className = 'text-xs text-gray-500 dark:text-gray-300 mt-1 flex items-center justify-between';

  const leftMeta = document.createElement('div');
  leftMeta.innerHTML = escapeHtml(data.user_name ?? '') + (Array.isArray(data.items) && data.items[0] ? ` • ${escapeHtml(data.items[0].name)}` : '');

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
  body.appendChild(meta);

  wrapper.appendChild(avatar);
  wrapper.appendChild(body);

  // click: mark read (optimistic UI update). NO MODAL open
  wrapper.addEventListener('click', async () => {
    // quick toast (optional)
    if (typeof showToast === 'function') {
      try { showToast(data.message ?? 'Notification', 'success'); } catch(e){ /* ignore */ }
    }

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
