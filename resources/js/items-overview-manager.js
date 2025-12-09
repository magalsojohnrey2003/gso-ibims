const CONDITION_LABELS = {
  good: 'Good',
  minor_damage: 'Minor Damage',
  damage: 'Damage',
  missing: 'Missing',
  not_received: 'Not Received',
};

const BLOCKED_STATUSES = new Set(['borrowed']);
const BLOCKED_MESSAGE = 'Cannot update condition while the item is currently borrowed.';

function selectAll(selector, context) {
  return Array.from((context || document).querySelectorAll(selector));
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function normalizeBase(url) {
  if (!url) return '';
  return url.endsWith('/') ? url.slice(0, -1) : url;
}

function toggleRowHighlight(row, active) {
  if (!row) return;
  row.classList.toggle('bg-purple-100', active);
  row.classList.toggle('bg-purple-50', active);
}

function updateHistoryButtonState(row, disabled) {
  const button = row?.querySelector('button');
  if (!button) return;
  button.disabled = disabled;
  button.classList.toggle('opacity-60', disabled);
  button.classList.toggle('cursor-not-allowed', disabled);
}

function normalizeStatus(value) {
  return String(value || '').toLowerCase();
}

function getRowStatus(row) {
  return normalizeStatus(row?.dataset.instanceStatus || '');
}

function isRowSelectable(row) {
  return row && !BLOCKED_STATUSES.has(getRowStatus(row));
}

function announceBlocked() {
  if (typeof window.showToast === 'function') {
    window.showToast(BLOCKED_MESSAGE, 'warning');
  } else {
    console.warn(BLOCKED_MESSAGE);
  }
}

async function updateInstanceCondition(endpoint, condition, csrfToken) {
  const response = await fetch(endpoint, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({ condition }),
    credentials: 'same-origin',
  });

  let payload = null;
  try {
    payload = await response.json();
  } catch (error) {
    /* ignore parse errors */
  }

  if (!response.ok) {
    const message = payload?.message || `Failed to update condition (HTTP ${response.status}).`;
    const error = new Error(message);
    error.payload = payload;
    throw error;
  }

  return payload || {};
}

function dispatchGlobalUpdates(payload) {
  if (!payload) return;
  const status = payload.condition === 'not_received'
    ? 'not_received'
    : (payload.inventory_status || payload.condition);
  window.dispatchEvent(new CustomEvent('return-items:condition-updated', {
    detail: { response: payload },
  }));
  if (payload.item_instance_id) {
    window.dispatchEvent(new CustomEvent('item-overview:condition-updated', {
      detail: {
        instanceId: payload.item_instance_id,
        status,
      },
    }));
  }
}

function initItemOverviewManager(section) {
  if (!section || section.__instancesManagerBound) return;
  section.__instancesManagerBound = true;

  const itemId = section.dataset.itemId || null;
  const updateBase = normalizeBase(section.dataset.updateBase || '/admin/item-instances');
  const csrfToken = getCsrfToken();

  const enableSelectionToggle = section.querySelector('[data-instance-enable-selection]');
  const conditionSelect = section.querySelector('[data-instance-condition]');
  const updateButton = section.querySelector('[data-instance-update]');
  const selectHeader = section.querySelector('[data-instance-select-header]');

  if (!enableSelectionToggle || !conditionSelect || !updateButton) {
    return;
  }

  const selectedIds = new Set();

  const updateButtonState = () => {
    Array.from(selectedIds).forEach((id) => {
      const row = section.querySelector(`[data-item-instance-id="${id}"]`);
      if (!isRowSelectable(row)) {
        selectedIds.delete(id);
      }
    });

    const hasSelection = selectedIds.size > 0;
    const conditionChosen = Boolean(conditionSelect.value);
    updateButton.disabled = !(hasSelection && conditionChosen);
  };

  const toggleSelectionMode = (enabled) => {
    if (selectHeader) {
      selectHeader.classList.toggle('hidden', !enabled);
    }

    selectAll('[data-instance-select-cell]', section).forEach((cell) => {
      const row = cell.closest('[data-instance-row]');
      const selectable = isRowSelectable(row);
      cell.classList.toggle('hidden', !enabled);
      const checkbox = cell.querySelector('[data-instance-checkbox]');
      if (checkbox) {
        const shouldDisable = !enabled || !selectable;
        checkbox.disabled = shouldDisable;
        if (shouldDisable) {
          checkbox.checked = false;
        }
        if (selectable) {
          delete checkbox.dataset.blocked;
        } else {
          checkbox.dataset.blocked = '1';
        }
      }
    });

    selectAll('[data-instance-row]', section).forEach((row) => {
      if (!enabled) {
        toggleRowHighlight(row, false);
      }
      updateHistoryButtonState(row, enabled);
      if (!isRowSelectable(row)) {
        toggleRowHighlight(row, false);
      }
    });

    if (!enabled) {
      selectedIds.clear();
      updateButtonState();
    }
  };

  const syncCheckboxSelection = (checkbox) => {
    const row = checkbox.closest('[data-instance-row]');
    if (!row) return;
    const id = row.dataset.itemInstanceId;
    if (!id) return;

    if (!isRowSelectable(row)) {
      checkbox.checked = false;
      announceBlocked();
      return;
    }

    if (checkbox.checked) {
      selectedIds.add(id);
    } else {
      selectedIds.delete(id);
    }

    toggleRowHighlight(row, checkbox.checked);
    updateButtonState();
  };

  const clearCurrentSelection = () => {
    selectedIds.clear();
    selectAll('[data-instance-checkbox]', section).forEach((checkbox) => {
      checkbox.checked = false;
      const row = checkbox.closest('[data-instance-row]');
      toggleRowHighlight(row, false);
    });
    updateButtonState();
  };

  const handleRowClick = (event) => {
    if (!enableSelectionToggle.checked) return;
    const row = event.target.closest('[data-instance-row]');
    if (!row) return;
    if (!isRowSelectable(row)) {
      announceBlocked();
      return;
    }
    if (event.target.closest('button') || event.target.closest('a')) return;
    if (event.target.matches('input, select, label')) return;

    const checkbox = row.querySelector('[data-instance-checkbox]');
    if (!checkbox || checkbox.disabled) return;
    checkbox.checked = !checkbox.checked;
    syncCheckboxSelection(checkbox);
  };

  const applyPayloadToRow = (id, payload) => {
    const row = section.querySelector(`[data-item-instance-id="${id}"]`);
    if (!row) return;

    toggleRowHighlight(row, false);
  };

  const submitUpdates = async () => {
    if (updateButton.disabled) return;
    const condition = conditionSelect.value;
    if (!condition) return;
    if (!selectedIds.size) return;

    const ids = Array.from(selectedIds);
    const successes = [];
    const errors = [];

    updateButton.disabled = true;

    for (const rawId of ids) {
      const endpoint = `${updateBase}/${encodeURIComponent(rawId)}/condition`;
      try {
        const payload = await updateInstanceCondition(endpoint, condition, csrfToken);
        payload.item_instance_id = payload.item_instance_id || rawId;
        successes.push(payload);
        applyPayloadToRow(rawId, payload);
        dispatchGlobalUpdates(payload);
        selectedIds.delete(String(rawId));
        const checkbox = section.querySelector(`[data-instance-checkbox][value="${rawId}"]`);
        if (checkbox) {
          checkbox.checked = false;
        }
      } catch (error) {
        errors.push(error.message || 'Update failed.');
        break;
      }
    }

    if (errors.length) {
      window.showToast?.(errors[0], 'error');
    } else {
      clearCurrentSelection();

      const summary = successes.length === 1
        ? (successes[0].message || `Condition updated to ${CONDITION_LABELS[condition] || condition}.`)
        : `${successes.length} items updated to ${CONDITION_LABELS[condition] || condition}.`;

      if (summary) {
        window.showToast?.(summary, 'success');
      }
    }

    updateButtonState();
  };

  section.addEventListener('click', handleRowClick);

  section.addEventListener('change', (event) => {
    const target = event.target;
    if (target === enableSelectionToggle) {
      toggleSelectionMode(target.checked);
      updateButtonState();
      return;
    }
    if (target === conditionSelect) {
      updateButtonState();
      return;
    }
    if (target?.matches('[data-instance-checkbox]')) {
      if (target.dataset.blocked === '1') {
        target.checked = false;
        announceBlocked();
        return;
      }
      syncCheckboxSelection(target);
    }
  });

  updateButton.addEventListener('click', submitUpdates);

  toggleSelectionMode(false);
  updateButtonState();

  const availableDisplay = document.querySelector(`[data-item-available-display][data-item-id="${itemId}"]`);
  if (availableDisplay) {
    availableDisplay.textContent = availableDisplay.textContent.trim();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  selectAll('[data-item-instance-manager]').forEach(initItemOverviewManager);
});
