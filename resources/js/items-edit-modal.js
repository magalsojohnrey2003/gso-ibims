const SELECTOR = '[data-edit-item-form]';
const FEEDBACK_SELECTOR = '[data-edit-feedback]';
const ERROR_SELECTOR = '[data-edit-error]';
const SUBMIT_SELECTOR = '[data-edit-submit]';
const CANCEL_SELECTOR = '[data-edit-cancel]';

const FIELD_LABELS = {
  year: 'Year',
  category: 'Category',
  gla: 'GLA',
  serial: 'Serial',
  office: 'Office',
};

const showToast = (typeof window !== 'undefined' && typeof window.showToast === 'function')
  ? window.showToast.bind(window)
  : (type, message) => {
      if (type === 'error') console.error(message);
      else console.log(message);
    };

function buildErrorSummary(fields) {
  if (!fields || !fields.size) return '';
  const labels = Array.from(fields).map((field) => FIELD_LABELS[field] || field);
  if (labels.length === 1) {
    return `${labels[0]} field is incorrect`;
  }
  return `${labels.join(' & ')} fields are incorrect`;
}

function showMessage(el, message) {
  if (!el) return;
  el.textContent = message;
  el.classList.remove('hidden');
}

function hideMessage(el) {
  if (!el) return;
  el.textContent = '';
  el.classList.add('hidden');
}

function toggleLoading(btn, state) {
  if (!btn) return;
  btn.disabled = state;
  btn.dataset.loading = state ? 'true' : 'false';
}

async function submitForm(form) {
  const action = form.getAttribute('action') || window.location.href;
  const method = (form.getAttribute('method') || 'POST').toUpperCase();
  const formData = new FormData(form);

  const response = await fetch(action, {
    method,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: formData,
  });

  const contentType = response.headers.get('content-type') || '';
  const isJson = contentType.includes('application/json');
  const payload = isJson ? await response.json() : null;

  if (response.ok) {
    return { ok: true, data: payload };
  }

  if ((response.status === 409 || response.status === 422) && payload) {
    const messages = [];
    if (payload.errors) {
      Object.values(payload.errors).forEach((arr) => {
        if (Array.isArray(arr)) messages.push(...arr);
      });
    }
    if (payload.message) messages.push(payload.message);
    throw new Error(messages.join(' ') || 'Validation failed.');
  }

  const message = payload?.message || `Request failed (${response.status}).`;
  throw new Error(message);
}

function wireEditForm(form) {
  const feedbackEl = form.querySelector(FEEDBACK_SELECTOR);
  const errorEl = form.querySelector(ERROR_SELECTOR);
  const submitBtn = form.querySelector(SUBMIT_SELECTOR);
  const cancelBtn = form.querySelector(CANCEL_SELECTOR);
  const modalName = form.dataset.modalName;

  const manager = form.__instanceManager ?? null;

  const clearMessages = () => {
    hideMessage(feedbackEl);
    hideMessage(errorEl);
  };

  cancelBtn?.addEventListener('click', () => {
    clearMessages();
    form.reset();
    if (manager && typeof manager.resetAll === 'function') {
      manager.resetAll();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearMessages();

    toggleLoading(submitBtn, true);

    try {
      if (manager && typeof manager.applyChanges === 'function') {
        const applyResult = await manager.applyChanges();
        if (!applyResult.ok) {
          const summary = applyResult.message || buildErrorSummary(applyResult.fields || new Set());
          if (summary) {
            showMessage(errorEl, summary);
            showToast('error', summary);
          }
          toggleLoading(submitBtn, false);
          return;
        }
      }

      const result = await submitForm(form);
      const data = result?.data || {};
      const message = data.message || 'Item details updated successfully.';

      // Show toast notification
      showToast('success', message);
      showMessage(feedbackEl, message);

      // Revert to readonly state on success
      setFormReadonly(form, true);

      if (modalName) {
        // Delay closing modal slightly to ensure toast is visible
        setTimeout(() => {
          window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
          // Reload to reflect changes
          setTimeout(() => {
            window.location.reload();
          }, 300);
        }, 500);
      } else {
        // If no modal name, just reload after a delay
        setTimeout(() => {
          window.location.reload();
        }, 500);
      }
    } catch (error) {
      console.error(error);
      const message = error?.message || 'Failed to update item details. Please try again.';
      showMessage(errorEl, message);
      showToast('error', message);
    } finally {
      toggleLoading(submitBtn, false);
    }
  });
}

// Edit state management
function setFormReadonly(form, readonly = true) {
  if (!form) return;

  // Get all input, textarea, and select elements
  const fields = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
  
  fields.forEach((field) => {
    if (readonly) {
      field.setAttribute('readonly', 'readonly');
      if (field.tagName === 'SELECT') {
        field.setAttribute('disabled', 'disabled');
      }
    } else {
      field.removeAttribute('readonly');
      if (field.tagName === 'SELECT') {
        field.removeAttribute('disabled');
      }
    }
  });

  // Toggle button visibility
  const editBtn = form.querySelector('[data-edit-mode-btn]');
  const cancelBtn = form.querySelector('[data-edit-cancel-btn]');
  const submitBtn = form.querySelector('[data-edit-submit]');

  if (readonly) {
    // Readonly state: show Edit, hide Update & Cancel
    editBtn?.classList.remove('hidden');
    cancelBtn?.classList.add('hidden');
    submitBtn?.classList.add('hidden');
  } else {
    // Edit state: hide Edit, show Update & Cancel
    editBtn?.classList.add('hidden');
    cancelBtn?.classList.remove('hidden');
    submitBtn?.classList.remove('hidden');
  }
}

// Handle enable edit event
window.addEventListener('edit-item:enable-edit', (event) => {
  const itemId = event.detail?.itemId;
  if (!itemId) return;

  const form = document.querySelector(`[data-edit-item-form][data-modal-name="edit-item-${itemId}"]`);
  if (form) {
    setFormReadonly(form, false);
  }
});

// Handle cancel edit event
window.addEventListener('edit-item:cancel-edit', (event) => {
  const itemId = event.detail?.itemId;
  if (!itemId) return;

  const form = document.querySelector(`[data-edit-item-form][data-modal-name="edit-item-${itemId}"]`);
  if (form) {
    // Reset form to original values
    form.reset();
    
    // Clear any messages
    const feedbackEl = form.querySelector(FEEDBACK_SELECTOR);
    const errorEl = form.querySelector(ERROR_SELECTOR);
    hideMessage(feedbackEl);
    hideMessage(errorEl);
    
    // Reset instance manager if exists
    const manager = form.__instanceManager ?? null;
    if (manager && typeof manager.resetAll === 'function') {
      manager.resetAll();
    }
    
    // Restore original rows (remove any added rows and restore removed rows)
    restoreOriginalRows(form);
    
    // Set back to readonly
    setFormReadonly(form, true);
  }
});

// Initialize forms in readonly state when modal opens
window.addEventListener('open-modal', (event) => {
  const modalName = event.detail;
  if (modalName && modalName.startsWith('edit-item-')) {
    setTimeout(() => {
      const form = document.querySelector(`[data-edit-item-form][data-modal-name="${modalName}"]`);
      if (form) {
        // Store original rows state
        storeOriginalRows(form);
        setFormReadonly(form, true);
      }
    }, 100);
  }
});

// Populate office dropdowns
function populateOfficeDropdowns(form) {
  const offices = Array.isArray(window.__serverOffices) ? window.__serverOffices : [];
  const selects = form.querySelectorAll('select[data-office-select]');
  
  selects.forEach(select => {
    // Keep the first option (placeholder) and current value
    const currentValue = select.value;
    select.innerHTML = '<option value="">Office</option>';
    
    offices.forEach(office => {
      const code = office.code || office.office_code || '';
      if (code) {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = code;
        if (code === currentValue) {
          option.selected = true;
        }
        select.appendChild(option);
      }
    });
    
    // If current value wasn't in the list, add it
    if (currentValue && !Array.from(select.options).some(opt => opt.value === currentValue)) {
      const option = document.createElement('option');
      option.value = currentValue;
      option.textContent = currentValue;
      option.selected = true;
      select.appendChild(option);
    }
  });
}

// Sync office dropdowns
function syncOfficeDropdowns(form) {
  const selects = form.querySelectorAll('select[data-sync-office]');
  
  selects.forEach(select => {
    select.addEventListener('change', (event) => {
      const newValue = event.target.value;
      // Update all other office dropdowns
      selects.forEach(otherSelect => {
        if (otherSelect !== select) {
          otherSelect.value = newValue;
        }
      });
    });
  });
}

// Update trash button states based on row count
function updateTrashButtonStates(container) {
  const rows = container.querySelectorAll('.edit-instance-row:not([data-removed="1"])');
  const trashButtons = container.querySelectorAll('.instance-remove-btn');
  
  // Disable all trash buttons if only one row remains
  if (rows.length <= 1) {
    trashButtons.forEach(btn => {
      btn.disabled = true;
      btn.classList.add('opacity-50', 'cursor-not-allowed');
      btn.title = 'Cannot remove the last row';
    });
  } else {
    trashButtons.forEach(btn => {
      btn.disabled = false;
      btn.classList.remove('opacity-50', 'cursor-not-allowed');
      btn.title = 'Remove this row';
    });
  }
}

// Handle trash button click to remove rows
function handleTrashButtonClick(form) {
  const container = form.querySelector('[data-edit-instances-container]');
  if (!container) return;
  
  container.addEventListener('click', (event) => {
    const btn = event.target.closest('.instance-remove-btn');
    if (!btn || btn.disabled) return;
    
    const row = btn.closest('.edit-instance-row');
    if (!row) return;
    
    const rows = container.querySelectorAll('.edit-instance-row:not([data-removed="1"])');
    
    // Prevent removing if only one row left
    if (rows.length <= 1) {
      return;
    }
    
    const instanceId = row.dataset.instanceId;
    
    // If it's an existing instance (not "new"), mark it as removed
    if (instanceId && instanceId !== 'new') {
      const confirmMessage = 'Remove this property number? It will be deleted when you update.';
      if (!window.confirm(confirmMessage)) return;
      
      // Mark as removed instead of deleting
      row.dataset.removed = '1';
      row.classList.add('opacity-50');
      
      // Disable all inputs in the row
      row.querySelectorAll('input, select').forEach(input => {
        input.disabled = true;
      });
      
      // Change trash icon to restore icon
      btn.innerHTML = '<i class="fas fa-rotate-left text-sm"></i>';
      btn.title = 'Restore this row';
      btn.classList.remove('text-red-600', 'hover:text-red-700');
      btn.classList.add('text-blue-600', 'hover:text-blue-700');
      
      // Store original icon
      btn.dataset.removed = '1';
    } else {
      // For new rows, just remove them from DOM
      row.remove();
      
      // Update quantity input
      const quantityInput = form.querySelector('[data-quantity-input]');
      if (quantityInput) {
        const currentQty = parseInt(quantityInput.value, 10) || 0;
        quantityInput.value = Math.max(currentQty - 1, parseInt(quantityInput.min, 10) || 1);
        quantityInput.dataset.initialQuantity = quantityInput.value;
      }
    }
    
    // Renumber remaining rows
    renumberRows(container);
    
    // Update trash button states
    updateTrashButtonStates(container);
  });
  
  // Also handle restore (click on restored row)
  container.addEventListener('click', (event) => {
    const btn = event.target.closest('.instance-remove-btn[data-removed="1"]');
    if (!btn) return;
    
    const row = btn.closest('.edit-instance-row');
    if (!row) return;
    
    // Restore the row
    row.dataset.removed = '';
    row.classList.remove('opacity-50');
    
    // Enable all inputs
    row.querySelectorAll('input, select').forEach(input => {
      input.disabled = false;
    });
    
    // Restore trash icon
    btn.innerHTML = '<i class="fas fa-trash text-sm"></i>';
    btn.title = 'Remove this row';
    btn.classList.remove('text-blue-600', 'hover:text-blue-700');
    btn.classList.add('text-red-600', 'hover:text-red-700');
    delete btn.dataset.removed;
    
    // Update trash button states
    updateTrashButtonStates(container);
  });
  
  // Initial state
  updateTrashButtonStates(container);
}

// Renumber rows after removal
function renumberRows(container) {
  const rows = container.querySelectorAll('.edit-instance-row');
  let visibleIndex = 1;
  
  rows.forEach((row) => {
    const isRemoved = row.dataset.removed === '1';
    const numberEl = row.querySelector('div[class*="rounded-full"]');
    
    if (!isRemoved && numberEl) {
      numberEl.textContent = visibleIndex;
      visibleIndex++;
    }
  });
}

// Store original rows HTML on form initialization
function storeOriginalRows(form) {
  const container = form.querySelector('[data-edit-instances-container]');
  if (!container) return;
  
  // Store the original HTML of the container
  form.__originalRowsHTML = container.innerHTML;
  
  // Store original quantity
  const quantityInput = form.querySelector('[data-quantity-input]');
  if (quantityInput) {
    form.__originalQuantity = quantityInput.value;
  }
}

// Restore original rows (on cancel)
function restoreOriginalRows(form) {
  const container = form.querySelector('[data-edit-instances-container]');
  if (!container || !form.__originalRowsHTML) return;
  
  // Restore the original HTML
  container.innerHTML = form.__originalRowsHTML;
  
  // Restore original quantity
  const quantityInput = form.querySelector('[data-quantity-input]');
  if (quantityInput && form.__originalQuantity) {
    quantityInput.value = form.__originalQuantity;
    quantityInput.dataset.initialQuantity = form.__originalQuantity;
  }
  
  // Re-populate office dropdowns
  populateOfficeDropdowns(form);
  
  // Re-sync office dropdowns
  syncOfficeDropdowns(form);
  
  // Update trash button states
  updateTrashButtonStates(container);
}

// Handle quantity change to add new rows
function handleQuantityChange(form) {
  const quantityInput = form.querySelector('[data-quantity-input]');
  if (!quantityInput) return;
  
  quantityInput.addEventListener('change', (event) => {
    const newQty = parseInt(event.target.value, 10);
    const initialQty = parseInt(quantityInput.dataset.initialQuantity || '0', 10);
    
    if (isNaN(newQty) || newQty < initialQty) {
      // Reset to minimum if trying to decrease
      event.target.value = initialQty;
      return;
    }
    
    const difference = newQty - initialQty;
    if (difference > 0) {
      addEmptyPropertyRows(form, difference);
      // Update the initial quantity
      quantityInput.dataset.initialQuantity = newQty;
    }
  });
}

// Add empty property number rows
function addEmptyPropertyRows(form, count) {
  const container = form.querySelector('[data-edit-instances-container]');
  if (!container) return;
  
  const existingRows = container.querySelectorAll('.edit-instance-row');
  const nextIndex = existingRows.length + 1;
  
  for (let i = 0; i < count; i++) {
    const rowIndex = nextIndex + i;
    const newRow = document.createElement('div');
    newRow.className = 'flex items-center gap-2 edit-instance-row bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-3 py-3';
    newRow.dataset.instanceId = 'new';
    
    newRow.innerHTML = `
      <div class="flex-none w-8 text-center">
        <div class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-200 font-medium text-sm">${rowIndex}</div>
      </div>
      
      <div class="flex items-center gap-2 flex-1">
        <input
          type="text"
          class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-year"
          value=""
          placeholder="Year"
          inputmode="numeric"
          maxlength="4">
        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>
        
        <input
          type="text"
          class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-category"
          value=""
          placeholder="Category"
          inputmode="numeric"
          maxlength="4">
        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>
        
        <input
          type="text"
          class="w-16 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-gla"
          value=""
          placeholder="GLA"
          inputmode="numeric"
          maxlength="4">
        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>
        
        <input
          type="text"
          class="w-20 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-serial"
          value=""
          placeholder="Serial"
          maxlength="5">
        <span class="text-gray-500 dark:text-gray-400 select-none">-</span>
        
        <select
          class="w-24 text-center text-sm rounded-md border-2 border-gray-400 dark:border-gray-500 px-2 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 instance-part-office"
          data-office-select
          data-sync-office>
          <option value="">Office</option>
        </select>
      </div>
      
      <button
        type="button"
        class="instance-remove-btn flex-none inline-flex items-center justify-center text-red-600 hover:text-red-700 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        aria-label="Remove instance">
        <i class="fas fa-trash text-sm"></i>
      </button>
    `;
    
    container.appendChild(newRow);
  }
  
  // Populate office dropdowns for new rows
  populateOfficeDropdowns(form);
  syncOfficeDropdowns(form);
  
  // Update trash button states
  updateTrashButtonStates(container);
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll(SELECTOR).forEach((form) => {
    wireEditForm(form);
    // Store original rows state
    storeOriginalRows(form);
    // Set initial readonly state
    setFormReadonly(form, true);
    // Setup quantity change handler
    handleQuantityChange(form);
    // Populate office dropdowns
    populateOfficeDropdowns(form);
    // Sync office dropdowns
    syncOfficeDropdowns(form);
    // Handle trash button clicks
    handleTrashButtonClick(form);

    // Wire up "Generate Model No." input in edit modal
    const panel = form.querySelector('[data-edit-serial-panel]');
    const generator = panel ? panel.querySelector('[data-model-generator]') : null;
    const applyModelToAll = () => {
      if (!panel || !generator) return;
      const val = (generator.value || '').toUpperCase();
      panel.querySelectorAll('.instance-part-model-no,[data-serial-model-input="model_no"]').forEach((input) => {
        if (input instanceof HTMLInputElement && !input.disabled) {
          input.value = val;
        }
      });
    };
    if (generator) {
      generator.addEventListener('input', applyModelToAll);
    }
  });
  
  // Handle close button clicks to restore original rows
  document.addEventListener('click', (event) => {
    const closeButton = event.target.closest('[data-modal-close-button]');
    if (closeButton) {
      const modalName = closeButton.getAttribute('data-modal-name');
      if (modalName && modalName.startsWith('edit-item-')) {
        const form = document.querySelector(`[data-edit-item-form][data-modal-name="${modalName}"]`);
        if (form) {
          restoreOriginalRows(form);
          setFormReadonly(form, true);
        }
      }
    }
  });
});
