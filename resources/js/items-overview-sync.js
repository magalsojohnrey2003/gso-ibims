const STATUS_CLASS_MAP = {
  available: 'bg-green-100 text-green-700',
  borrowed: 'bg-indigo-100 text-indigo-700',
  returned: 'bg-emerald-100 text-emerald-700',
  missing: 'bg-red-100 text-red-700',
  damaged: 'bg-amber-100 text-amber-700',
  minor_damage: 'bg-yellow-100 text-yellow-700',
  pending: 'bg-gray-200 text-gray-700',
  unknown: 'bg-gray-200 text-gray-700',
};

const STATUS_LABEL_MAP = {
  borrowed: 'Borrowed',
  minor_damage: 'Minor Damage',
};

function normalizeStatus(value) {
  if (!value) return 'unknown';
  return String(value).toLowerCase();
}

function toLabel(status) {
  const key = normalizeStatus(status);
  if (STATUS_LABEL_MAP[key]) return STATUS_LABEL_MAP[key];
  return key
    .split('_')
    .filter(Boolean)
    .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
    .join(' ') || 'Unknown';
}

function applyStatusToBadge(badge, status) {
  if (!badge) return;
  const key = normalizeStatus(status);
  const classes = STATUS_CLASS_MAP[key] || STATUS_CLASS_MAP.unknown;
  const base = badge.dataset.badgeBase || '';
  badge.className = base ? `${base} ${classes}` : `${classes}`;
  badge.textContent = toLabel(status);
}

function updateInstanceStatus(itemInstanceId, status) {
  if (!itemInstanceId) return;
  const row = document.querySelector(`[data-item-instance-id="${itemInstanceId}"]`);
  if (!row) return;
  const badge = row.querySelector('[data-instance-status]');
  applyStatusToBadge(badge, status);
}

function updateAvailableCount(itemId, qty) {
  if (!itemId || qty === undefined || qty === null) return;
  const row = document.querySelector(`[data-item-row="${itemId}"]`);
  if (!row) return;
  const cell = row.querySelector('[data-item-available]');
  if (!cell) return;
  const span = cell.querySelector('span');
  if (!span) return;

  const value = Number(qty);
  span.textContent = Number.isFinite(value) ? value : qty;
  span.classList.remove('text-green-600', 'text-red-600');
  span.classList.add(value > 0 ? 'text-green-600' : 'text-red-600', 'font-semibold');
}

function handleCollected(event) {
  const detail = event?.detail || {};
  const response = detail.response || {};
  const instances = Array.isArray(response.instances) ? response.instances : [];
  instances.forEach((instance) => {
    updateInstanceStatus(instance.item_instance_id, instance.status);
  });
}

function handleConditionUpdated(event) {
  const detail = event?.detail || {};
  const response = detail.response || {};
  if (response.item_instance_id) {
    updateInstanceStatus(response.item_instance_id, response.inventory_status || response.condition);
  }
  if (response.item_id !== undefined && response.available_qty !== undefined) {
    updateAvailableCount(response.item_id, response.available_qty);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  window.addEventListener('return-items:collected', handleCollected);
  window.addEventListener('return-items:condition-updated', handleConditionUpdated);
});
