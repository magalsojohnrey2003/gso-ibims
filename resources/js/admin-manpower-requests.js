document.addEventListener('DOMContentLoaded', () => {
  if (!window.ADMIN_MANPOWER) return;
  const CSRF_TOKEN = window.ADMIN_MANPOWER?.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const tbody = document.getElementById('adminManpowerTableBody');
  const search = document.getElementById('admin-manpower-search');
  const statusFilter = document.getElementById('admin-manpower-status');
  const manageRolesBtn = document.getElementById('openManageRoles');
  const rolesTableBody = document.getElementById('adminRolesTableBody');
  const saveRoleBtn = document.getElementById('adminSaveRole');
  const roleNameInput = document.getElementById('adminRoleName');
  const approveFields = document.querySelectorAll('[data-approve-field]');
  const reductionReasonWrap = document.getElementById('adminReductionReasonWrap');
  const reductionReasonSelect = document.getElementById('adminReductionReason');
  const assignedNamesInput = document.getElementById('adminAssignedNamesInput');
  const assignedNamesChips = document.getElementById('adminAssignedNamesChips');
  const viewFields = document.querySelectorAll('[data-view-field]');
  const approvedQuantityInput = document.getElementById('adminApprovedQuantity');
  const confirmApproveBtn = document.getElementById('confirmAdminApproval');
  const rejectionCard = document.getElementById('adminManpowerRejectionCard');
  const rejectionSubjectView = document.querySelector('[data-view-field="rejection_subject"]');
  const rejectionDetailView = document.querySelector('[data-view-field="rejection_detail"]');
  const rejectReasonOptions = document.getElementById('manpowerRejectReasonOptions');
  const rejectReasonSelectConfirmBtn = document.getElementById('manpowerRejectReasonSelectConfirmBtn');
  const rejectReasonSelectCancelBtn = document.getElementById('manpowerRejectReasonSelectCancelBtn');
  const rejectReasonOtherRadio = document.getElementById('manpowerRejectReasonOtherOption');
  const rejectReasonSubjectInput = document.getElementById('manpowerRejectSubjectInput');
  const rejectReasonDetailInput = document.getElementById('manpowerRejectDetailInput');
  const rejectReasonCustomBackBtn = document.getElementById('manpowerRejectReasonCustomBackBtn');
  const rejectReasonCustomConfirmBtn = document.getElementById('manpowerRejectReasonCustomConfirmBtn');
  const rejectReasonViewSubject = document.getElementById('manpowerRejectReasonViewSubject');
  const rejectReasonViewDetail = document.getElementById('manpowerRejectReasonViewDetail');
  const rejectReasonDeleteName = document.getElementById('manpowerRejectReasonDeleteName');
  const rejectReasonDeleteUsage = document.getElementById('manpowerRejectReasonDeleteUsage');
  const rejectReasonDeleteCancelBtn = document.getElementById('manpowerRejectReasonDeleteCancelBtn');
  const rejectReasonDeleteConfirmBtn = document.getElementById('manpowerRejectReasonDeleteConfirmBtn');
  const ICONS = {
    check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>',
    xMark: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>',
    eye: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    trash: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21a48.11 48.11 0 00-3.478-.397M7.53 5.79a48.108 48.108 0 00-3.4.273M15 5.25V4.5A1.5 1.5 0 0013.5 3h-3A1.5 1.5 0 009 4.5v.75m7.5 0a48.667 48.667 0 013.468.34M9 5.25a48.667 48.667 0 00-3.468.34M4.5 6.75h15" /></svg>'
  };
  let CACHE = [];
  let ACTIVE_REQUEST = null;
  let APPROVE_ASSIGNED_NAMES = [];
  let REJECT_REQUEST = null;
  const OTHER_REJECTION_REASON_KEY = '__other__';
  const REJECTION_REASONS_ENDPOINT = window.REJECTION_REASONS_ENDPOINT || '/admin/rejection-reasons';
  let REJECTION_REASONS_CACHE = [];
  const rejectionFlowState = {
    requestId: null,
    selectedReasonId: null,
    prevModal: null,
  };

  const SHORT_MONTHS = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.'];

  const formatRequestCode = (row) => {
    if (!row) return '';
    const formatted = typeof row.formatted_request_id === 'string' ? row.formatted_request_id.trim() : '';
    if (formatted) return formatted;
    const rawId = row.id ?? null;
    if (!rawId) return '';
    return `MP-${String(rawId).padStart(4, '0')}`;
  };

  const fetchRows = async () => {
    try {
      const params = new URLSearchParams();
      const q = search.value.trim();
      if (q) params.set('q', q);
      const sv = statusFilter.value.trim();
      if (sv) params.set('status', sv);
      const res = await fetch(`${window.ADMIN_MANPOWER.list}?${params.toString()}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      CACHE = Array.isArray(data) ? data : [];
      render();
    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-red-600">Failed to load.</td></tr>`;
    }
  };

  const pickRow = (id) => CACHE.find(r => String(r.id) === String(id));

  const formatDate = (value) => {
    if (!value) return null;
    const safe = value.includes('T') ? value : value.replace(' ', 'T');
    const date = new Date(safe);
    if (Number.isNaN(date.getTime())) return null;
    const month = SHORT_MONTHS[date.getMonth()] || '';
    const day = date.getDate();
    const year = date.getFullYear();
    if (!month || !day || !year) return null;
    return `${month} ${day}, ${year}`;
  };

  const formatDateDisplay = (value) => formatDate(value) || '—';

  const badgeHtml = (status) => {
    status = (status||'').toLowerCase();
    if (status === 'pending') return document.getElementById('badge-status-pending')?.innerHTML || status;
    if (status === 'validated') return document.getElementById('badge-status-validated')?.innerHTML || status;
    if (status === 'approved') return document.getElementById('badge-status-approved')?.innerHTML || status;
    if (status === 'rejected') return document.getElementById('badge-status-rejected')?.innerHTML || status;
    return status || '—';
  };

  const formatRoleList = (breakdown = []) => {
    if (!Array.isArray(breakdown) || !breakdown.length) return '';
    const normalized = breakdown
      .map((entry) => {
        const qty = Number(entry?.approved_quantity ?? entry?.quantity ?? 0);
        const label = String(entry?.role_name || entry?.role || '').trim();
        const normalizedQty = Number.isFinite(qty) && qty > 0 ? qty : 0;
        if (!label || normalizedQty < 1) return null;
        return `Manpower-${label} (x${normalizedQty})`;
      })
      .filter(Boolean);
    return normalized.length ? normalized.join(', ') : '';
  };

  const formatAssignedNames = (names) => {
    if (!Array.isArray(names)) return '—';
    const normalized = names
      .map((name) => String(name || '').trim())
      .filter(Boolean);
    return normalized.length ? normalized.join(', ') : '—';
  };

  const buildRoleBreakdownList = (breakdown = []) => {
    if (!Array.isArray(breakdown) || !breakdown.length) return '';
    return breakdown
      .map((entry) => {
        const requested = Number(entry?.quantity ?? 0);
        const approved = Number(entry?.approved_quantity ?? 0);
        const label = (entry?.role_name || entry?.role || '').trim() || 'Role';
        const parts = [`${requested} x ${label}`];
        if (!Number.isNaN(approved) && approved > 0) {
          parts.push(`(Approved ${approved})`);
        }
        return `<li>${parts.join(' ')}</li>`;
      })
      .join('');
  };

  const escapeAttr = (value) => String(value ?? '').replace(/["'&<>]/g, (char) => {
    switch (char) {
      case '"': return '&quot;';
      case "'": return '&#39;';
      case '&': return '&amp;';
      case '<': return '&lt;';
      case '>': return '&gt;';
      default: return char;
    }
  });

  const buildLetterPreview = (url) => {
    const noLetter = `<div class="inline-flex items-center gap-2 text-sm text-gray-500"><i class="fas fa-file-circle-xmark text-lg"></i><span>No letter uploaded.</span></div>`;
    if (!url) {
      return noLetter;
    }

    const safeUrl = escapeAttr(url);
    const base = safeUrl.split('?')[0].toLowerCase();
    const header = `<div class="inline-flex items-center gap-2 text-sm font-semibold text-sky-700"><i class="fas fa-file-alt text-base"></i><span>Uploaded Letter</span></div>`;
    const linkHtml = `<a href='${safeUrl}' target='_blank' rel='noopener' class='inline-flex items-center gap-2 text-xs font-medium text-sky-600 hover:text-sky-700 transition-colors'><i class="fas fa-arrow-up-right-from-square text-[0.7rem]"></i><span>Open uploaded letter</span></a>`;

    if (/(\.png|\.jpe?g|\.gif|\.webp|\.bmp|\.svg)$/.test(base)) {
      return `<div class='space-y-2'>
        ${header}
        <figure class='rounded-lg border border-sky-100 bg-sky-50/60 p-3 shadow-sm'>
          <img src='${safeUrl}' alt='Uploaded letter preview' loading='lazy' class='w-full max-h-72 object-contain rounded-md' />
        </figure>
        <div>${linkHtml}</div>
      </div>`;
    }

    if (/\.pdf$/.test(base)) {
      return `<div class='space-y-2'>
        ${header}
        <div class='rounded-lg border border-sky-100 bg-sky-50/60 shadow-sm overflow-hidden'>
          <iframe src='${safeUrl}' title='Uploaded letter preview' class='w-full h-72'></iframe>
        </div>
        <div>${linkHtml}</div>
      </div>`;
    }

    return `<div class='space-y-2'>${header}<div>${linkHtml}</div></div>`;
  };

  const setRejectionReasonsCache = (reasons) => {
    REJECTION_REASONS_CACHE = Array.isArray(reasons) ? reasons.slice() : [];
    REJECTION_REASONS_CACHE.sort((a, b) => {
      const usageA = Number(a?.usage_count ?? 0);
      const usageB = Number(b?.usage_count ?? 0);
      if (usageA !== usageB) {
        return usageB - usageA;
      }
      const subjectA = (a?.subject ?? '').toLowerCase();
      const subjectB = (b?.subject ?? '').toLowerCase();
      if (subjectA < subjectB) return -1;
      if (subjectA > subjectB) return 1;
      return 0;
    });
  };

  const fetchRejectionReasons = async (force = false) => {
    if (!force && REJECTION_REASONS_CACHE.length) {
      return REJECTION_REASONS_CACHE;
    }

    const res = await fetch(REJECTION_REASONS_ENDPOINT, { headers: { Accept: 'application/json' } });
    let payload = null;
    try {
      payload = await res.json();
    } catch (error) {
      payload = null;
    }

    if (!res.ok) {
      throw new Error(payload?.message || `Failed to load rejection reasons (status ${res.status})`);
    }

    const reasons = Array.isArray(payload) ? payload : [];
    setRejectionReasonsCache(reasons);
    return REJECTION_REASONS_CACHE;
  };

  const getRejectionReasonById = (reasonId) => {
    const numeric = Number(reasonId);
    if (Number.isNaN(numeric)) return null;
    return REJECTION_REASONS_CACHE.find((reason) => Number(reason.id) === numeric) || null;
  };

  const resetRejectionFlow = () => {
    rejectionFlowState.requestId = null;
    rejectionFlowState.selectedReasonId = null;
    rejectionFlowState.prevModal = null;
    REJECT_REQUEST = null;

    const radios = document.querySelectorAll('input[name="manpowerRejectReasonChoice"]');
    radios.forEach((radio) => {
      radio.checked = false;
    });
    if (rejectReasonOtherRadio) {
      rejectReasonOtherRadio.checked = false;
    }
    if (rejectReasonSubjectInput) {
      rejectReasonSubjectInput.value = '';
    }
    if (rejectReasonDetailInput) {
      rejectReasonDetailInput.value = '';
    }
    updateRejectConfirmButton();
  };

  const renderRejectionReasonList = () => {
    if (!rejectReasonOptions) return;

    rejectReasonOptions.innerHTML = '';

    if (!REJECTION_REASONS_CACHE.length) {
      const empty = document.createElement('p');
      empty.className = 'text-sm text-gray-500';
      empty.textContent = 'No saved rejection reasons yet.';
      rejectReasonOptions.appendChild(empty);
      return;
    }

    REJECTION_REASONS_CACHE.forEach((reason) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'flex items-start justify-between gap-3 border border-gray-200 rounded-lg p-3 hover:border-purple-300 transition';
      const radioId = `manpowerRejectReason${reason.id}`;
      wrapper.innerHTML = `
        <label class="flex items-start gap-3 flex-1 cursor-pointer" for="${radioId}">
          <input type="radio" id="${radioId}" class="mt-1 text-purple-600 focus:ring-purple-500" name="manpowerRejectReasonChoice" value="${escapeAttr(String(reason.id))}">
          <div>
            <div class="text-sm font-semibold text-gray-900">${escapeAttr(reason.subject || 'Untitled reason')}</div>
            <div class="text-xs text-gray-500 mt-0.5">Used ${escapeAttr(String(reason.usage_count ?? 0))} time(s)</div>
          </div>
        </label>
        <div class="flex items-center gap-2 shrink-0">
          <button type="button" class="text-sm text-indigo-600 hover:text-indigo-700" data-action="view" data-reason-id="${escapeAttr(String(reason.id))}">View</button>
          <button type="button" class="text-sm text-red-500 hover:text-red-600" data-action="remove" data-reason-id="${escapeAttr(String(reason.id))}">Remove</button>
        </div>
      `;
      rejectReasonOptions.appendChild(wrapper);
    });

    if (rejectionFlowState.selectedReasonId) {
      const selector = `input[name="manpowerRejectReasonChoice"][value="${rejectionFlowState.selectedReasonId}"]`;
      const radio = rejectReasonOptions.querySelector(selector);
      if (radio) {
        radio.checked = true;
      }
    }
  };

  const updateRejectConfirmButton = () => {
    if (!rejectReasonSelectConfirmBtn) return;
    const selected = rejectionFlowState.selectedReasonId;
    rejectReasonSelectConfirmBtn.disabled = !selected;
    let label = 'Confirm Reject';
    if (selected === OTHER_REJECTION_REASON_KEY) {
      label = 'Next';
    }
    rejectReasonSelectConfirmBtn.textContent = label;
  };

  const openRejectionSelectModal = (requestId, options = {}) => {
    if (!REJECTION_REASONS_CACHE.length) {
      openRejectionCustomModal(requestId, { fromSelection: false });
      return;
    }

    const preserveSelection = Boolean(options.preserveSelection);
    if (!preserveSelection) {
      rejectionFlowState.selectedReasonId = null;
    }

    rejectionFlowState.requestId = requestId;
    rejectionFlowState.prevModal = null;

    renderRejectionReasonList();
    updateRejectConfirmButton();

    if (rejectionFlowState.selectedReasonId === OTHER_REJECTION_REASON_KEY && rejectReasonOtherRadio) {
      rejectReasonOtherRadio.checked = true;
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'adminManpowerRejectSelectModal' }));
  };

  const openRejectionCustomModal = (requestId, options = {}) => {
    rejectionFlowState.requestId = requestId;
    rejectionFlowState.prevModal = options.fromSelection ? 'select' : null;
    if (options.fromSelection) {
      rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
    }

    if (rejectReasonSubjectInput) {
      rejectReasonSubjectInput.value = options.subject ?? '';
    }
    if (rejectReasonDetailInput) {
      rejectReasonDetailInput.value = options.detail ?? '';
    }

    if (rejectReasonSubjectInput) {
      rejectReasonSubjectInput.focus();
    }

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'adminManpowerRejectCustomModal' }));
  };

  const openRejectionDetailModal = (reason) => {
    if (rejectReasonViewSubject) {
      rejectReasonViewSubject.textContent = reason?.subject ?? '';
    }
    if (rejectReasonViewDetail) {
      rejectReasonViewDetail.textContent = reason?.detail ?? '';
    }
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'adminManpowerRejectViewModal' }));
  };

  const openRejectionDeleteModal = (reasonId) => {
    const reason = getRejectionReasonById(reasonId);
    if (!reason) {
      window.showToast?.('The selected rejection reason is no longer available.', 'error');
      return;
    }
    if (rejectReasonDeleteName) {
      rejectReasonDeleteName.textContent = reason.subject || 'Untitled reason';
    }
    if (rejectReasonDeleteUsage) {
      rejectReasonDeleteUsage.textContent = `Used ${Number(reason.usage_count ?? 0)} time(s)`;
    }
    if (rejectReasonDeleteConfirmBtn) {
      rejectReasonDeleteConfirmBtn.dataset.reasonId = String(reason.id);
    }
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'adminManpowerRejectDeleteModal' }));
  };

  const deleteRejectionReason = async (reasonId, button) => {
    if (!reasonId) return;
    if (button) button.disabled = true;
    try {
      const res = await fetch(`${REJECTION_REASONS_ENDPOINT}/${encodeURIComponent(reasonId)}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': CSRF_TOKEN,
          Accept: 'application/json',
        },
      });
      let payload = null;
      try {
        payload = await res.json();
      } catch (error) {
        payload = null;
      }

      if (!res.ok) {
        throw new Error(payload?.message || `Failed to remove rejection reason (status ${res.status})`);
      }

      const numericId = Number(reasonId);
      const index = REJECTION_REASONS_CACHE.findIndex((reason) => Number(reason.id) === numericId);
      if (index > -1) {
        REJECTION_REASONS_CACHE.splice(index, 1);
      }

      if (REJECTION_REASONS_CACHE.length) {
        const nextIndex = index >= 0 ? Math.min(index, REJECTION_REASONS_CACHE.length - 1) : 0;
        const nextReason = REJECTION_REASONS_CACHE[nextIndex] ?? null;
        rejectionFlowState.selectedReasonId = nextReason ? String(nextReason.id) : null;
      } else {
        rejectionFlowState.selectedReasonId = null;
      }

      setRejectionReasonsCache(REJECTION_REASONS_CACHE);
      renderRejectionReasonList();
      updateRejectConfirmButton();

      window.dispatchEvent(new CustomEvent('close-modal', { detail: 'adminManpowerRejectDeleteModal' }));
      window.showToast?.(payload?.message || 'Rejection reason removed.', 'success');

      if (!REJECTION_REASONS_CACHE.length) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'adminManpowerRejectSelectModal' }));
        if (rejectionFlowState.requestId) {
          openRejectionCustomModal(rejectionFlowState.requestId, { fromSelection: false });
        } else {
          resetRejectionFlow();
        }
      }
    } catch (error) {
      console.error(error);
      window.showToast?.(error?.message || 'Failed to remove rejection reason.', 'error');
    } finally {
      if (button) button.disabled = false;
    }
  };

  const submitRejectionDecision = async ({ requestId, reasonId, subject, detail, button, modalsToClose = [] }) => {
    if (!requestId) {
      window.showToast?.('No manpower request selected.', 'error');
      return;
    }

    const closingList = Array.isArray(modalsToClose) ? modalsToClose : [];
    closingList.forEach((modalName) => {
      window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));
    });

    if (button) button.disabled = true;
    try {
      await updateStatus(requestId, 'rejected', {
        rejection_reason_id: reasonId ?? null,
        rejection_reason_subject: subject ?? null,
        rejection_reason_detail: detail ?? null,
      });

      if (reasonId != null) {
        const idx = REJECTION_REASONS_CACHE.findIndex((reason) => Number(reason.id) === Number(reasonId));
        if (idx > -1) {
          const updated = { ...REJECTION_REASONS_CACHE[idx] };
          updated.usage_count = Number(updated.usage_count ?? 0) + 1;
          REJECTION_REASONS_CACHE[idx] = updated;
          setRejectionReasonsCache(REJECTION_REASONS_CACHE);
          renderRejectionReasonList();
        }
      }
      resetRejectionFlow();
    } catch (error) {
      console.error(error);
      if (closingList.length) {
        closingList.forEach((modalName) => {
          window.dispatchEvent(new CustomEvent('open-modal', { detail: modalName }));
        });
      }
    } finally {
      if (button) button.disabled = false;
    }
  };

  const saveCustomRejectionReason = async (button) => {
    if (!rejectionFlowState.requestId) {
      window.showToast?.('No manpower request selected.', 'error');
      return;
    }

    const subject = rejectReasonSubjectInput?.value?.trim() || '';
    const detail = rejectReasonDetailInput?.value?.trim() || '';

    if (!subject) {
      window.showToast?.('Please enter a rejection subject.', 'warning');
      rejectReasonSubjectInput?.focus();
      return;
    }

    if (!detail) {
      window.showToast?.('Please provide the detailed rejection reason.', 'warning');
      rejectReasonDetailInput?.focus();
      return;
    }

    if (button) button.disabled = true;

    try {
      const res = await fetch(REJECTION_REASONS_ENDPOINT, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF_TOKEN,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ subject, detail }),
      });

      let payload = null;
      try {
        payload = await res.json();
      } catch (error) {
        payload = null;
      }

      if (!res.ok) {
        throw new Error(payload?.message || `Failed to save rejection reason (status ${res.status})`);
      }

      const reasonData = payload?.reason ?? null;
      if (reasonData) {
        const idx = REJECTION_REASONS_CACHE.findIndex((reason) => Number(reason.id) === Number(reasonData.id));
        if (idx > -1) {
          REJECTION_REASONS_CACHE[idx] = reasonData;
        } else {
          REJECTION_REASONS_CACHE.push(reasonData);
        }
        setRejectionReasonsCache(REJECTION_REASONS_CACHE);
      }

      const subjectForUpdate = reasonData?.subject ?? subject;
      const detailForUpdate = reasonData?.detail ?? detail;

      await submitRejectionDecision({
        requestId: rejectionFlowState.requestId,
        reasonId: reasonData?.id ?? null,
        subject: subjectForUpdate,
        detail: detailForUpdate,
        button,
        modalsToClose: ['adminManpowerRejectCustomModal', 'adminManpowerRejectSelectModal'],
      });

      if (rejectReasonSubjectInput) rejectReasonSubjectInput.value = '';
      if (rejectReasonDetailInput) rejectReasonDetailInput.value = '';
    } catch (error) {
      console.error(error);
      window.showToast?.(error?.message || 'Failed to save rejection reason.', 'error');
    } finally {
      if (button) button.disabled = false;
    }
  };

  const handleCustomRejectionBack = () => {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'adminManpowerRejectCustomModal' }));

    if (rejectionFlowState.prevModal === 'select' && REJECTION_REASONS_CACHE.length) {
      rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
      openRejectionSelectModal(rejectionFlowState.requestId, { preserveSelection: true });
      if (rejectReasonOtherRadio) {
        rejectReasonOtherRadio.checked = true;
      }
      updateRejectConfirmButton();
    } else {
      resetRejectionFlow();
    }
  };

  const beginRejectionFlow = async (row) => {
    if (!row) return;
    REJECT_REQUEST = row;
    rejectionFlowState.requestId = row.id;

    try {
      const reasons = await fetchRejectionReasons(false);
      if (!reasons.length) {
        rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
        openRejectionCustomModal(row.id, {
          fromSelection: false,
          subject: row.rejection_reason_subject || '',
          detail: row.rejection_reason_detail || '',
        });
        return;
      }

      rejectionFlowState.selectedReasonId = null;
      renderRejectionReasonList();
      updateRejectConfirmButton();

      window.dispatchEvent(new CustomEvent('open-modal', { detail: 'adminManpowerRejectSelectModal' }));
    } catch (error) {
      console.error(error);
      window.showToast?.(error?.message || 'Failed to load rejection reasons.', 'error');
    }
  };

  const bindRejectionReasonEvents = () => {
    if (rejectReasonOptions) {
      rejectReasonOptions.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) return;
        if (target.name !== 'manpowerRejectReasonChoice') return;
        rejectionFlowState.selectedReasonId = target.value;
        updateRejectConfirmButton();
      });

      rejectReasonOptions.addEventListener('click', (event) => {
        const button = event.target instanceof HTMLElement ? event.target.closest('button[data-action]') : null;
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();

        const reasonId = button.dataset.reasonId;
        if (!reasonId) return;
        const action = button.dataset.action;

        if (action === 'view') {
          const reason = getRejectionReasonById(reasonId);
          if (!reason) {
            window.showToast?.('The selected rejection reason is no longer available.', 'error');
            return;
          }
          openRejectionDetailModal(reason);
        } else if (action === 'remove') {
          openRejectionDeleteModal(reasonId);
        }
      });
    }

    rejectReasonOtherRadio?.addEventListener('change', (event) => {
      const input = event.target;
      if (!(input instanceof HTMLInputElement)) return;
      if (input.checked) {
        rejectionFlowState.selectedReasonId = OTHER_REJECTION_REASON_KEY;
        updateRejectConfirmButton();
      }
    });

    rejectReasonSelectConfirmBtn?.addEventListener('click', async () => {
      if (!rejectionFlowState.requestId) {
        window.showToast?.('No manpower request selected.', 'error');
        return;
      }

      const selected = rejectionFlowState.selectedReasonId;
      if (!selected) {
        window.showToast?.('Please select a rejection reason.', 'warning');
        return;
      }

      if (selected === OTHER_REJECTION_REASON_KEY) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'adminManpowerRejectSelectModal' }));
        openRejectionCustomModal(rejectionFlowState.requestId, { fromSelection: true });
        return;
      }

      const reason = getRejectionReasonById(selected);
      if (!reason) {
        window.showToast?.('The selected rejection reason is no longer available.', 'error');
        return;
      }

      rejectReasonSelectConfirmBtn.disabled = true;
      try {
        await submitRejectionDecision({
          requestId: rejectionFlowState.requestId,
          reasonId: reason.id,
          subject: reason.subject,
          detail: reason.detail,
          button: rejectReasonSelectConfirmBtn,
          modalsToClose: ['adminManpowerRejectSelectModal'],
        });
      } finally {
        rejectReasonSelectConfirmBtn.disabled = false;
      }
    });

    rejectReasonSelectCancelBtn?.addEventListener('click', () => {
      resetRejectionFlow();
    });

    rejectReasonCustomConfirmBtn?.addEventListener('click', async () => {
      await saveCustomRejectionReason(rejectReasonCustomConfirmBtn);
    });

    rejectReasonCustomBackBtn?.addEventListener('click', () => {
      handleCustomRejectionBack();
    });

    rejectReasonDeleteConfirmBtn?.addEventListener('click', async () => {
      const reasonId = rejectReasonDeleteConfirmBtn.dataset.reasonId;
      if (!reasonId) {
        window.showToast?.('Unable to remove the selected reason.', 'error');
        return;
      }
      await deleteRejectionReason(reasonId, rejectReasonDeleteConfirmBtn);
    });

    rejectReasonDeleteCancelBtn?.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('close-modal', { detail: 'adminManpowerRejectDeleteModal' }));
    });
  };

  const render = () => {
    if (!CACHE.length) {
      const template = document.getElementById('admin-manpower-empty-state-template');
      tbody.innerHTML = '';
      if (template?.content?.firstElementChild) {
        tbody.appendChild(template.content.firstElementChild.cloneNode(true));
      } else {
        tbody.innerHTML = `<tr><td colspan="6" class="py-10 text-center text-gray-500">No manpower requests found</td></tr>`;
      }
      return;
    }
    tbody.innerHTML = CACHE.map(r => {
      const requestCode = formatRequestCode(r);
      const borrowDate = formatDateDisplay(r.start_at);
      const returnDate = formatDateDisplay(r.end_at);
      return `<tr data-manpower-id='${r.id}'>
        <td class='px-6 py-3 font-semibold text-gray-900'>${requestCode || ''}</td>
        <td class='px-6 py-3'>${r.user ? r.user.name : '—'}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${borrowDate}</td>
        <td class='px-6 py-3 text-sm text-gray-700'>${returnDate}</td>
        <td class='px-6 py-3'>${badgeHtml(r.status)}</td>
        <td class='px-6 py-3'>${actionButtons(r)}</td>
      </tr>`;
    }).join('');
  };

  const actionButtons = (r) => {
    const status = String(r.status || '').toLowerCase();
    if (status === 'pending') {
      return `<div class="flex items-center justify-center gap-2">
        <button data-action="approve" class="btn-action btn-accept h-10 w-10" title="Validate">
          <span class="sr-only">Validate</span>
          ${ICONS.check}
        </button>
        <button data-action="reject" class="btn-action btn-reject h-10 w-10" title="Reject">
          <span class="sr-only">Reject</span>
          ${ICONS.xMark}
        </button>
      </div>`;
    }
    if (status === 'validated') {
      return `<span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border border-sky-200 bg-sky-50 text-sky-700"><i class="fas fa-clock text-[0.7rem]"></i><span>Pending Submission</span></span>`;
    }
    return `<div class="flex items-center justify-center gap-2">
      <button data-action="view" class="btn-action btn-view h-10 w-10" title="View">
        <span class="sr-only">View</span>
        ${ICONS.eye}
      </button>
    </div>`;
  };

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const tr = btn.closest('tr[data-manpower-id]');
    if (!tr) return;
    const id = tr.dataset.manpowerId;
    const action = btn.dataset.action;
    const row = pickRow(id);
    if (!row) return;

    if (action === 'approve') {
      ACTIVE_REQUEST = row;
      hydrateApproveModal(row);
      openModal('adminManpowerApproveModal');
    } else if (action === 'reject') {
      beginRejectionFlow(row);
    } else if (action === 'view') {
      hydrateViewModal(row);
      openModal('adminManpowerViewModal');
    }
  });

  const openModal = (name) => window.dispatchEvent(new CustomEvent('open-modal', {detail: name}));
  const closeModal = (name) => window.dispatchEvent(new CustomEvent('close-modal', {detail: name}));

  const hydrateApproveModal = (row) => {
    resetApprovalForm();
    approvedQuantityInput.value = row.approved_quantity || row.quantity;
    if (approvedQuantityInput) {
      approvedQuantityInput.max = row.quantity;
    }
      if (reductionReasonSelect) {
        reductionReasonSelect.value = row.reduction_reason || '';
      }
      if (Array.isArray(row.assigned_personnel_names)) {
        APPROVE_ASSIGNED_NAMES = row.assigned_personnel_names.filter((name) => String(name || '').trim());
        renderAssignedNameChips();
      }
      approveFields.forEach(el => {
        const key = el.dataset.approveField;
        if (key === 'borrow_date') {
          el.textContent = formatDateDisplay(row.start_at);
        } else if (key === 'return_date') {
          el.textContent = formatDateDisplay(row.end_at);
        } else if (key === 'letter') {
          el.innerHTML = buildLetterPreview(row.letter_url);
        } else if (key === 'user') {
          el.textContent = row.user?.name || '—';
        } else if (key === 'role') {
            const breakdown = Array.isArray(row.role_breakdown) ? row.role_breakdown : [];
            if (breakdown.length) {
              el.innerHTML = `<ul class="list-disc list-inside space-y-1 text-sm text-gray-800">${buildRoleBreakdownList(breakdown)}</ul>`;
            } else {
              el.textContent = formatRoleList([]) || row.role || '—';
            }
        } else if (key === 'quantity') {
          el.textContent = row.quantity;
        } else if (key === 'status') {
          el.innerHTML = badgeHtml(row.status);
        } else {
          el.textContent = row[key] || '—';
        }
      });
      
      toggleReductionReason();
  };

  const hydrateViewModal = (row) => {
    viewFields.forEach(el => {
      const key = el.dataset.viewField;
      if (key === 'borrow_date') {
        el.textContent = formatDateDisplay(row.start_at);
      } else if (key === 'return_date') {
        el.textContent = formatDateDisplay(row.end_at);
      } else if (key === 'quantity') {
        const approved = row.approved_quantity ? `${row.approved_quantity} / ` : '';
        el.textContent = `${approved}${row.quantity}`;
      } else if (key === 'letter') {
        el.innerHTML = buildLetterPreview(row.letter_url);
      } else if (key === 'user') {
        el.textContent = row.user?.name || '—';
      } else if (key === 'status') {
        el.innerHTML = badgeHtml(row.status);
      } else if (key === 'role') {
        const breakdown = Array.isArray(row.role_breakdown) ? row.role_breakdown : [];
        if (breakdown.length) {
          el.innerHTML = `<ul class="list-disc list-inside space-y-1 text-sm text-gray-800">${buildRoleBreakdownList(breakdown)}</ul>`;
        } else {
          el.textContent = formatRoleList([]) || row.role || '—';
        }
      } else if (key === 'reduction_reason') {
        el.textContent = (row.reduction_reason || '').trim() || '—';
      } else if (key === 'assigned_personnel_names') {
        el.textContent = formatAssignedNames(row.assigned_personnel_names);
      } else {
        el.textContent = row[key] || '—';
      }
    });

    const hasRejection = Boolean((row.rejection_reason_subject || '').trim() || (row.rejection_reason_detail || '').trim());
    if (rejectionCard) {
      if (hasRejection) {
        rejectionCard.classList.remove('hidden');
        if (rejectionSubjectView) {
          rejectionSubjectView.textContent = row.rejection_reason_subject || '—';
        }
        if (rejectionDetailView) {
          rejectionDetailView.textContent = row.rejection_reason_detail || '—';
        }
      } else {
        rejectionCard.classList.add('hidden');
        if (rejectionSubjectView) rejectionSubjectView.textContent = '—';
        if (rejectionDetailView) rejectionDetailView.textContent = '—';
      }
    }

  };

  confirmApproveBtn?.addEventListener('click', async () => {
    if (!ACTIVE_REQUEST) return;
    const qty = parseInt(approvedQuantityInput.value, 10);
    if (!qty || qty < 1) {
      window.showToast?.('Approved quantity must be at least 1.', 'warning');
      return;
    }
    if (qty > ACTIVE_REQUEST.quantity) {
      window.showToast?.('Approved quantity cannot exceed requested quantity.', 'warning');
      return;
    }
    const requestedQty = Number(ACTIVE_REQUEST.quantity ?? 0);
    const reductionReason = reductionReasonSelect?.value?.trim() || '';
    const names = APPROVE_ASSIGNED_NAMES.map((name) => name.trim()).filter(Boolean);
    if (requestedQty && qty < requestedQty && !reductionReason) {
      window.showToast?.('Please select a reduction reason.', 'warning');
      reductionReasonSelect?.focus();
      return;
    }
    const payload = { approved_quantity: qty };
    if (requestedQty && qty < requestedQty) {
      payload.reduction_reason = reductionReason;
    }
    if (names.length) {
      payload.assigned_personnel_names = names;
    }

    closeModal('adminManpowerApproveModal');
    try {
      await updateStatus(ACTIVE_REQUEST.id, 'validated', payload);
      ACTIVE_REQUEST = null;
    } catch (error) {
      console.error(error);
      openModal('adminManpowerApproveModal');
    }
  });

  const enforceQuantityBounds = () => {
    if (!approvedQuantityInput) return;
    const raw = parseInt(approvedQuantityInput.value, 10);
    if (Number.isNaN(raw)) return;
    const maxAttr = approvedQuantityInput.max;
    const maxCandidate = Number(ACTIVE_REQUEST?.quantity ?? (maxAttr !== '' ? maxAttr : NaN));
    const max = Number.isFinite(maxCandidate) ? maxCandidate : null;
    if (max !== null && raw > max) {
      approvedQuantityInput.value = max;
      return;
    }
    const minAttr = approvedQuantityInput.min;
    const minCandidate = Number(minAttr !== '' ? minAttr : NaN);
    const min = Number.isFinite(minCandidate) ? minCandidate : 1;
    if (raw < min) {
      approvedQuantityInput.value = min;
    }
    toggleReductionReason();
  };

  approvedQuantityInput?.addEventListener('input', enforceQuantityBounds);
  approvedQuantityInput?.addEventListener('change', enforceQuantityBounds);

  function toggleReductionReason() {
    if (!approvedQuantityInput || !reductionReasonWrap) return;
    const requested = Number(ACTIVE_REQUEST?.quantity ?? 0);
    const approved = Number(approvedQuantityInput.value ?? 0);
    const show = Number.isFinite(approved) && approved > 0 && approved < requested;
    reductionReasonWrap.hidden = !show;
    if (!show && reductionReasonSelect) {
      reductionReasonSelect.value = '';
    }
  }

  function resetApprovalForm() {
    APPROVE_ASSIGNED_NAMES = [];
    if (approvedQuantityInput) {
      approvedQuantityInput.value = '';
    }
    if (reductionReasonSelect) {
      reductionReasonSelect.value = '';
    }
    renderAssignedNameChips();
    toggleReductionReason();
  }

  function renderAssignedNameChips() {
    if (!assignedNamesChips) return;
    assignedNamesChips.innerHTML = '';
    if (!APPROVE_ASSIGNED_NAMES.length) return;
    APPROVE_ASSIGNED_NAMES.forEach((name, index) => {
      const chip = document.createElement('span');
      chip.className = 'inline-flex items-center gap-2 rounded-full bg-emerald-100 text-emerald-800 px-3 py-1 text-xs font-semibold border border-emerald-200';
      const label = document.createElement('span');
      label.textContent = name;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.index = String(index);
      btn.className = 'text-emerald-700 hover:text-emerald-900 focus:outline-none';
      btn.innerHTML = '&times;';
      chip.appendChild(label);
      chip.appendChild(btn);
      assignedNamesChips.appendChild(chip);
    });
  }

  assignedNamesChips?.addEventListener('click', (event) => {
    const btn = event.target instanceof HTMLElement ? event.target.closest('button[data-index]') : null;
    if (!btn) return;
    const idx = parseInt(btn.dataset.index || '', 10);
    if (Number.isNaN(idx)) return;
    APPROVE_ASSIGNED_NAMES.splice(idx, 1);
    renderAssignedNameChips();
  });

  function addAssignedName(raw) {
    const normalized = String(raw || '').replace(/\s+/g, ' ').trim();
    if (!normalized) return;
    const exists = APPROVE_ASSIGNED_NAMES.some((name) => name.toLowerCase() === normalized.toLowerCase());
    if (exists) return;
    APPROVE_ASSIGNED_NAMES.push(normalized);
    renderAssignedNameChips();
  }

  assignedNamesInput?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      addAssignedName(assignedNamesInput.value);
      assignedNamesInput.value = '';
    } else if (event.key === ',' || event.key === 'Tab') {
      const value = assignedNamesInput.value;
      if (value.trim()) {
        event.preventDefault();
        addAssignedName(value);
        assignedNamesInput.value = '';
      }
    }
  });

  assignedNamesInput?.addEventListener('blur', () => {
    if (!assignedNamesInput.value.trim()) return;
    addAssignedName(assignedNamesInput.value);
    assignedNamesInput.value = '';
  });

  const updateStatus = async (id, status, extra = {}) => {
    try {
      const res = await fetch(window.ADMIN_MANPOWER.status(id), {
        method: 'POST',
        headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.ADMIN_MANPOWER.csrf,'Content-Type':'application/json'},
        body: JSON.stringify({status, ...extra})
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');

      const normalizedStatus = String(status || '').toLowerCase();
      const fallbackMessages = {
        validated: 'Manpower request validated.',
        approved: 'Manpower request approved.',
        rejected: 'Manpower request rejected.',
        pending: 'Manpower request moved back to pending.',
      };
      const fallbackTypes = {
        rejected: 'info',
        pending: 'info',
      };

      const toastMessage = data.message || fallbackMessages[normalizedStatus] || 'Manpower request status updated.';
      const toastType = fallbackTypes[normalizedStatus] || 'success';
      window.showToast?.(toastMessage, toastType);

      await fetchRows();
      return true;
    } catch (e) { 
      console.error(e); 
      window.showToast?.(e.message || 'Status update failed', 'error');
      throw e;
    }
  };

  // Manage roles
  const fetchRoles = async () => {
    if (!rolesTableBody) return;
    try {
      const res = await fetch(window.ADMIN_MANPOWER.roles.list, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const data = await res.json();
      renderRoles(Array.isArray(data) ? data : []);
    } catch (e) {
      console.error(e);
      rolesTableBody.innerHTML = `<tr><td colspan="2" class="px-4 py-4 text-center text-red-500">Failed to load roles.</td></tr>`;
    }
  };

  const renderRoles = (roles) => {
    if (!roles.length) {
      rolesTableBody.innerHTML = `<tr><td colspan="2" class="px-4 py-4 text-center text-gray-500">No roles available.</td></tr>`;
      return;
    }
    rolesTableBody.innerHTML = roles.map(role => `
      <tr data-role-id='${role.id}'>
        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">${role.name}</td>
        <td class="px-4 py-3 text-center">
          <button data-role-delete class="btn-action btn-delete h-9 w-9" title="Remove role">
            <span class="sr-only">Remove role</span>
            ${ICONS.trash}
          </button>
        </td>
      </tr>
    `).join('');
  };

  rolesTableBody?.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-role-delete]');
    if (!btn) return;
    const tr = btn.closest('tr[data-role-id]');
    if (!tr) return;
    const id = tr.dataset.roleId;
    if (!window.confirm('Delete this role?')) return;
    try {
      const res = await fetch(window.ADMIN_MANPOWER.roles.delete(id), {
        method: 'DELETE',
        headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.ADMIN_MANPOWER.csrf}
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      window.showToast?.('Role deleted.', 'success');
      await fetchRoles();
    } catch (err) {
      console.error(err);
      window.showToast?.(err.message || 'Failed to delete role.', 'error');
    }
  });

  saveRoleBtn?.addEventListener('click', async () => {
    const name = roleNameInput.value.trim();
    if (!name) {
      window.showToast?.('Role type is required.', 'warning');
      return;
    }
    try {
      const res = await fetch(window.ADMIN_MANPOWER.roles.store, {
        method: 'POST',
        headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.ADMIN_MANPOWER.csrf,'Content-Type':'application/json'},
        body: JSON.stringify({name})
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed');
      roleNameInput.value = '';
      window.showToast?.('Role added.', 'success');
      await fetchRoles();
    } catch (err) {
      console.error(err);
      window.showToast?.(err.message || 'Failed to save role.', 'error');
    }
  });

  manageRolesBtn?.addEventListener('click', () => {
    fetchRoles();
    openModal('adminManageRolesModal');
  });

  search?.addEventListener('input', () => { fetchRows(); });
  statusFilter?.addEventListener('change', () => { fetchRows(); });

  bindRejectionReasonEvents();
  fetchRows();
});