const STATUS_CLASS_MAP = {
  available: 'bg-green-100 text-green-700',
  borrowed: 'bg-indigo-100 text-indigo-700',
  returned: 'bg-emerald-100 text-emerald-700',
  missing: 'bg-red-100 text-red-700',
  not_received: 'bg-gray-200 text-gray-800',
  damaged: 'bg-amber-100 text-amber-700',
  minor_damage: 'bg-yellow-100 text-yellow-700',
  pending: 'bg-gray-200 text-gray-700',
  unknown: 'bg-gray-200 text-gray-700',
};

const STATUS_ICON_MAP = {
  available: 'fa-check-circle',
  borrowed: 'fa-box',
  returned: 'fa-arrow-left',
  missing: 'fa-exclamation-triangle',
  not_received: 'fa-triangle-exclamation',
  damaged: 'fa-exclamation-triangle',
  minor_damage: 'fa-exclamation-circle',
  pending: 'fa-clock',
  unknown: 'fa-question-circle',
};

const STATUS_LABEL_MAP = {
  borrowed: 'Borrowed',
  minor_damage: 'Minor Damage',
  not_received: 'Not Received',
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
  const icon = STATUS_ICON_MAP[key] || STATUS_ICON_MAP.unknown;
  const base = badge.dataset.badgeBase || 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold';
  badge.className = `${base} ${classes}`;
  badge.innerHTML = `<i class="fas ${icon} text-xs"></i><span>${toLabel(status)}</span>`;
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
  const cell = row?.querySelector('[data-item-available]');
  const span = cell?.querySelector('span');
  const detail = document.querySelector(`[data-item-available-display][data-item-id="${itemId}"]`);
  const value = Number(qty);
  const numericText = Number.isFinite(value) ? value : qty;

  if (span) {
    span.textContent = numericText;
    span.classList.remove('text-green-600', 'text-red-600');
    span.classList.add(value > 0 ? 'text-green-600' : 'text-red-600', 'font-semibold');
  }

  if (detail) {
    detail.textContent = numericText;
    const positiveClass = detail.dataset.positiveClass || 'text-green-600';
    const negativeClass = detail.dataset.negativeClass || 'text-red-600';
    detail.classList.remove(positiveClass, negativeClass);
    detail.classList.add(value > 0 ? positiveClass : negativeClass);
  }
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
  const status = response.condition === 'not_received'
    ? 'not_received'
    : (response.inventory_status || response.condition);
  if (response.item_instance_id) {
    updateInstanceStatus(response.item_instance_id, status);
  }
  if (response.item_id !== undefined && response.available_qty !== undefined) {
    updateAvailableCount(response.item_id, response.available_qty);
  }
}

function updateCoLocatedStatus(instanceId, status) {
  if (!instanceId) return;
  const row = document.querySelector(`[data-item-instance-id="${instanceId}"]`);
  if (!row) return;
  const badge = row.querySelector('[data-instance-status]');
  applyStatusToBadge(badge, status);
}

function handleInstanceConditionUpdated(event) {
  const detail = event?.detail || {};
  updateCoLocatedStatus(detail.instanceId, detail.status);
}

document.addEventListener('DOMContentLoaded', () => {
  window.addEventListener('return-items:collected', handleCollected);
  window.addEventListener('return-items:condition-updated', handleConditionUpdated);
  window.addEventListener('item-overview:condition-updated', handleInstanceConditionUpdated);
});
