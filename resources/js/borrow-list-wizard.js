function formatSummaryDate(value) {
    if (!value) return '--';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return '--';
    return parsed.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
}

function formatUsageLabel(startValue, endValue) {
    if (!startValue || !endValue) return '--';
    const toLabel = (time) => {
        const [hour, minute] = time.split(':').map(Number);
        const date = new Date();
        date.setHours(hour, minute, 0, 0);
        return date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit' });
    };
    return `${toLabel(startValue)} - ${toLabel(endValue)}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const wizardContainer = document.getElementById('borrowWizardSteps');
    const form = document.getElementById('borrowListForm');
    if (!wizardContainer || !form) return;

    const steps = Array.from(wizardContainer.querySelectorAll('[data-step]'));
    const indicatorItems = Array.from(document.querySelectorAll('#borrowWizardIndicator [data-step-index]'));

    const step1NextBtn = document.getElementById('step1NextBtn');
    const step2NextBtn = document.getElementById('step2NextBtn');
    const step2BackBtn = document.getElementById('step2BackBtn');
    const step3BackBtn = document.getElementById('step3BackBtn');
    const openConfirmModalBtn = document.getElementById('openConfirmModalBtn');
    const confirmBorrowRequestBtn = document.getElementById('confirmBorrowRequestBtn');
    const letterInput = document.getElementById('support_letter');
    const letterFileName = document.getElementById('letterFileName');
    let letterPond = null;
    
    // Function to get FilePond instance reliably
    const getLetterPond = () => {
        if (letterPond) return letterPond;
        if (!letterInput) return null;
        
        // Try to get from stored instance
        if (letterInput._filepondInstance) {
            letterPond = letterInput._filepondInstance;
            return letterPond;
        }
        
        // Try to find by DOM element
        const pondElement = letterInput.parentElement?.querySelector('.filepond--root');
        const FilePondLib = window.FilePond || (typeof FilePond !== 'undefined' ? FilePond : null);
        if (pondElement && FilePondLib && FilePondLib.find) {
            letterPond = FilePondLib.find(pondElement);
            return letterPond;
        }
        
        return null;
    };

    const summaryBorrowDates = document.getElementById('summaryBorrowDates');
    const summaryAddress = document.getElementById('summaryAddress');
    const summaryManpowerList = document.getElementById('summaryManpowerList');
    const summaryManpowerEmpty = document.getElementById('summaryManpowerEmpty');
    const summaryItemsList = document.getElementById('summaryItemsList');
    const summaryPurposeOffice = document.getElementById('summaryPurposeOffice');
    const summaryPurpose = document.getElementById('summaryPurpose');
    const summaryUsage = document.getElementById('summaryUsage');

    const modalBorrowDate = document.getElementById('modalBorrowDate');
    const modalReturnDate = document.getElementById('modalReturnDate');
    const modalItemsList = document.getElementById('modalItemsList');
    const modalManpowerList = document.getElementById('modalManpowerList');
    const modalManpowerEmpty = document.getElementById('modalManpowerEmpty');
    const modalAddress = document.getElementById('modalAddress');
    const modalLetterName = document.getElementById('modalLetterName');
    const modalPurposeOffice = document.getElementById('modalPurposeOffice');
    const modalPurpose = document.getElementById('modalPurpose');
    const modalUsage = document.getElementById('modalUsage');

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const locationHidden = document.getElementById('location');
    const purposeOfficeInput = document.getElementById('purpose_office');
    const purposeInput = document.getElementById('purpose');
    const usageStartInput = document.getElementById('usage_start');
    const usageEndInput = document.getElementById('usage_end');
    const manpowerList = document.getElementById('manpowerRequirementsList');
    const manpowerTemplate = document.getElementById('manpowerRequirementTemplate');
    const manpowerEmptyState = document.getElementById('manpowerRequirementsEmpty');
    const addManpowerBtn = document.getElementById('addManpowerRequirementBtn');
    const manpowerModalRoleSelect = document.getElementById('manpowerModalRole');
    const manpowerModalQuantityInput = document.getElementById('manpowerModalQuantity');
    const manpowerModalIndexInput = document.getElementById('manpowerModalIndex');
    const manpowerModalConfirmBtn = document.getElementById('manpowerModalConfirmBtn');
    const manpowerModalCancelBtn = document.getElementById('manpowerModalCancelBtn');
    const manpowerModalTitle = document.getElementById('manpowerModalTitle');
    const manpowerModalRoleEmpty = document.getElementById('manpowerModalRoleEmpty');
    const manpowerModalDismissButtons = Array.from(document.querySelectorAll('[data-manpower-modal-dismiss]'));

    const manpowerRoleChoices = manpowerModalRoleSelect
        ? Array.from(manpowerModalRoleSelect.options)
            .filter((option) => option.value)
            .map((option) => ({
                id: option.value,
                label: option.textContent?.trim() || option.innerText?.trim() || option.value,
            }))
        : [];

    const defaultManpowerRoleName = manpowerList?.dataset.defaultRoleName?.trim() || 'Assist';
    const rawDefaultQty = parseInt(manpowerList?.dataset.defaultRoleQty || '10', 10);
    const defaultManpowerRoleQuantity = Number.isFinite(rawDefaultQty) && rawDefaultQty > 0
        ? Math.min(rawDefaultQty, 99)
        : 10;
    let defaultManpowerInjected = Boolean(manpowerList?.querySelector('[data-auto-default="true"]'));
    let defaultManpowerAttempted = defaultManpowerInjected;

    const findDefaultManpowerRole = () => {
        if (!defaultManpowerRoleName || !manpowerRoleChoices.length) {
            return null;
        }
        const target = defaultManpowerRoleName.toLowerCase();
        return manpowerRoleChoices.find((choice) => (choice.label || '').toLowerCase() === target) || null;
    };

    const getSelectedRoleIds = () => {
        return new Set(getManpowerEntries().map((entry) => String(entry.roleId)));
    };

    const getRoleOptionsForModal = (currentRoleId = null, currentRoleLabel = null) => {
        const selectedIds = getSelectedRoleIds();
        if (currentRoleId !== null && currentRoleId !== undefined && currentRoleId !== '') {
            selectedIds.delete(String(currentRoleId));
        }
        const available = manpowerRoleChoices.filter((choice) => !selectedIds.has(String(choice.id)) || String(choice.id) === String(currentRoleId));

        if (
            currentRoleId !== null
            && currentRoleId !== undefined
            && currentRoleId !== ''
            && !available.some((choice) => String(choice.id) === String(currentRoleId))
        ) {
            available.push({
                id: String(currentRoleId),
                label: currentRoleLabel || 'Selected role',
            });
        }

        return available;
    };

    const rebuildModalRoleOptions = (options, selectedValue = '') => {
        if (!manpowerModalRoleSelect) return;

        while (manpowerModalRoleSelect.firstChild) {
            manpowerModalRoleSelect.removeChild(manpowerModalRoleSelect.firstChild);
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select role';
        manpowerModalRoleSelect.appendChild(placeholder);

        options.forEach((choice) => {
            const option = document.createElement('option');
            option.value = choice.id;
            option.textContent = choice.label;
            manpowerModalRoleSelect.appendChild(option);
        });

        manpowerModalRoleSelect.value = selectedValue || '';
    };

    const updateManpowerAddButtonState = () => {
        if (!addManpowerBtn) return;
        const remainingOptions = getRoleOptionsForModal();
        const hasAvailable = remainingOptions.length > 0;
        addManpowerBtn.disabled = !hasAvailable || manpowerRoleChoices.length === 0;
        addManpowerBtn.classList.toggle('opacity-50', addManpowerBtn.disabled);
        addManpowerBtn.classList.toggle('cursor-not-allowed', addManpowerBtn.disabled);
    };

    let editingManpowerRow = null;
    let manpowerModalMode = 'add';

    let currentStep = 0;
    let locationValid = !!(locationHidden?.value);
    const hasAtLeastOneItem = () => document.querySelectorAll('#borrowListItems [data-item-entry]').length > 0;
    let currentUsageLabel = formatUsageLabel(
        usageStartInput?.value || null,
        usageEndInput?.value || null,
    ) || '--';

    const getManpowerEntries = () => {
        if (!manpowerList) return [];
        return Array.from(manpowerList.querySelectorAll('[data-manpower-entry]'))
            .map((row) => {
                const roleInput = row.querySelector('[data-manpower-role-input]');
                const quantityInput = row.querySelector('[data-manpower-quantity-input]');
                const roleId = roleInput?.value?.trim() || row.dataset.roleId || '';
                const quantityValue = quantityInput?.value?.trim() || row.dataset.quantity || '';
                const quantity = quantityValue === '' ? NaN : parseInt(quantityValue, 10);
                if (!roleId || Number.isNaN(quantity) || quantity < 1) {
                    return null;
                }
                const roleNameEl = row.querySelector('[data-manpower-role-label]');
                const roleName = roleNameEl?.textContent?.trim() || 'Role';
                return {
                    roleId,
                    roleName,
                    quantity,
                    row,
                };
            })
            .filter(Boolean);
    };

    const renderManpowerList = (listEl, emptyEl, entries) => {
        if (!listEl || !emptyEl) return;
        listEl.innerHTML = '';
        if (!entries.length) {
            emptyEl.hidden = false;
            emptyEl.classList.remove('hidden');
            listEl.classList.add('hidden');
            return;
        }

        emptyEl.hidden = true;
        emptyEl.classList.add('hidden');
        listEl.classList.remove('hidden');
        entries.forEach(({ roleName, quantity }) => {
            const li = document.createElement('li');
            li.textContent = `${quantity} × ${roleName}`;
            listEl.appendChild(li);
        });
    };

    const updateManpowerEmptyState = () => {
        if (!manpowerEmptyState) return;
        const hasRows = Boolean(manpowerList?.querySelector('[data-manpower-entry]'));
        manpowerEmptyState.hidden = hasRows;
        manpowerEmptyState.classList.toggle('hidden', hasRows);
        updateManpowerAddButtonState();
    };

    const assignManpowerFieldNames = () => {
        if (!manpowerList) return;
        const rows = Array.from(manpowerList.querySelectorAll('[data-manpower-entry]'));
        rows.forEach((row, index) => {
            row.dataset.manpowerIndex = String(index);
            const roleInput = row.querySelector('[data-manpower-role-input]');
            const qtyInput = row.querySelector('[data-manpower-quantity-input]');
            if (roleInput) {
                roleInput.name = `manpower_requirements[${index}][role_id]`;
            }
            if (qtyInput) {
                qtyInput.name = `manpower_requirements[${index}][quantity]`;
            }
        });
        updateManpowerEmptyState();
    };

    const emitManpowerUpdated = () => {
        const entries = getManpowerEntries();
        window.dispatchEvent(new CustomEvent('borrow:manpower-updated', { detail: { entries } }));
    };

    const injectDefaultManpowerIfNeeded = () => {
        if (defaultManpowerAttempted) return;
        if (!manpowerList || !manpowerTemplate) {
            defaultManpowerAttempted = true;
            return;
        }

        const hasItems = document.querySelectorAll('[data-item-entry]').length > 0;
        if (!hasItems) {
            return;
        }

        if (getManpowerEntries().length > 0) {
            return;
        }

        const roleChoice = findDefaultManpowerRole();
        if (!roleChoice) {
            defaultManpowerAttempted = true;
            return;
        }

        const fragment = manpowerTemplate?.content?.cloneNode(true);
        const newRow = fragment?.firstElementChild;
        if (!newRow) {
            defaultManpowerAttempted = true;
            return;
        }

        newRow.dataset.roleId = roleChoice.id;
        newRow.dataset.quantity = String(defaultManpowerRoleQuantity);
        newRow.dataset.autoDefault = 'true';

        const roleInput = newRow.querySelector('[data-manpower-role-input]');
        const quantityInput = newRow.querySelector('[data-manpower-quantity-input]');
        if (roleInput) roleInput.value = roleChoice.id;
        if (quantityInput) quantityInput.value = String(defaultManpowerRoleQuantity);

        const roleLabelEl = newRow.querySelector('[data-manpower-role-label]');
        const quantityLabelEl = newRow.querySelector('[data-manpower-quantity-label]');
        if (roleLabelEl) roleLabelEl.textContent = roleChoice.label;
        if (quantityLabelEl) quantityLabelEl.textContent = String(defaultManpowerRoleQuantity);

        manpowerList.appendChild(newRow);
        bindManpowerRow(newRow);
        assignManpowerFieldNames();
        emitManpowerUpdated();
        defaultManpowerInjected = true;
        defaultManpowerAttempted = true;
    };

    const clampManpowerQuantity = (input) => {
        if (!input) return;
        const rawValue = parseInt(input.value || '0', 10);
        if (Number.isNaN(rawValue) || rawValue < 1) {
            input.value = '1';
            return;
        }
        if (rawValue > 99) {
            input.value = '99';
        }
    };

    const closeManpowerModal = () => {
        editingManpowerRow = null;
        manpowerModalMode = 'add';
        if (manpowerModalIndexInput) manpowerModalIndexInput.value = '';
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'manpowerRequirementModal' }));
    };

    const openManpowerModal = ({ mode, row = null }) => {
        if (!manpowerModalRoleSelect || !manpowerModalQuantityInput || !manpowerModalConfirmBtn) {
            return;
        }

        manpowerModalMode = mode === 'edit' ? 'edit' : 'add';
        editingManpowerRow = manpowerModalMode === 'edit' ? row : null;

        const currentRoleId = manpowerModalMode === 'edit'
            ? (row?.dataset.roleId || row?.querySelector('[data-manpower-role-input]')?.value || '')
            : '';

        const currentRoleLabel = manpowerModalMode === 'edit'
            ? (row?.querySelector('[data-manpower-role-label]')?.textContent?.trim() || '')
            : null;

        const options = getRoleOptionsForModal(currentRoleId, currentRoleLabel);
        const selectedValue = manpowerModalMode === 'edit' ? String(currentRoleId) : '';
        rebuildModalRoleOptions(options, selectedValue);

        const hasOptions = options.length > 0;
        if (manpowerModalRoleEmpty) {
            manpowerModalRoleEmpty.classList.toggle('hidden', hasOptions);
        }
        manpowerModalRoleSelect.disabled = !hasOptions;
        if (manpowerModalConfirmBtn) {
            manpowerModalConfirmBtn.disabled = !hasOptions;
        }

        if (manpowerModalTitle) {
            manpowerModalTitle.textContent = manpowerModalMode === 'edit'
                ? 'Edit Manpower Role'
                : 'Add Manpower Role';
        }

        if (manpowerModalIndexInput) {
            manpowerModalIndexInput.value = manpowerModalMode === 'edit' && row
                ? (row.dataset.manpowerIndex || '')
                : '';
        }

        let defaultQuantity = 1;
        if (manpowerModalMode === 'edit' && row) {
            const existingQuantity = parseInt(row.dataset.quantity || row.querySelector('[data-manpower-quantity-input]')?.value || '1', 10);
            if (!Number.isNaN(existingQuantity) && existingQuantity > 0) {
                defaultQuantity = Math.min(existingQuantity, 99);
            }
        }
        manpowerModalQuantityInput.value = String(defaultQuantity);

        if (manpowerModalMode === 'add' && hasOptions) {
            manpowerModalRoleSelect.value = options[0]?.id || '';
        }

        setTimeout(() => manpowerModalRoleSelect.focus(), 0);

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'manpowerRequirementModal' }));
    };

    const applyManpowerModalSelection = () => {
        if (!manpowerModalRoleSelect || !manpowerModalQuantityInput) {
            return;
        }

        if (manpowerModalRoleSelect.disabled) {
            window.showToast?.('All available roles have already been added.', 'info');
            return;
        }

        const roleId = manpowerModalRoleSelect.value?.trim();
        if (!roleId) {
            window.showToast?.('Please select a manpower role.', 'warning');
            manpowerModalRoleSelect.focus();
            return;
        }

        clampManpowerQuantity(manpowerModalQuantityInput);
        const quantityValue = manpowerModalQuantityInput.value?.trim() || '1';
        const quantity = parseInt(quantityValue, 10);
        if (Number.isNaN(quantity) || quantity < 1 || quantity > 99) {
            window.showToast?.('Manpower quantities must be between 1 and 99.', 'warning');
            manpowerModalQuantityInput.focus();
            return;
        }

        const roleChoice = manpowerRoleChoices.find((choice) => String(choice.id) === String(roleId));
        const roleLabel = roleChoice?.label || manpowerModalRoleSelect.options[manpowerModalRoleSelect.selectedIndex]?.text?.trim() || 'Role';

        if (manpowerModalMode === 'edit' && editingManpowerRow) {
            editingManpowerRow.dataset.roleId = roleId;
            editingManpowerRow.dataset.quantity = String(quantity);

            const roleInput = editingManpowerRow.querySelector('[data-manpower-role-input]');
            const quantityInput = editingManpowerRow.querySelector('[data-manpower-quantity-input]');
            if (roleInput) roleInput.value = roleId;
            if (quantityInput) quantityInput.value = String(quantity);

            const roleLabelEl = editingManpowerRow.querySelector('[data-manpower-role-label]');
            const quantityLabelEl = editingManpowerRow.querySelector('[data-manpower-quantity-label]');
            if (roleLabelEl) roleLabelEl.textContent = roleLabel;
            if (quantityLabelEl) quantityLabelEl.textContent = String(quantity);
        } else {
            const fragment = manpowerTemplate?.content?.cloneNode(true);
            const newRow = fragment?.firstElementChild;
            if (!newRow) {
                window.showToast?.('Unable to add manpower role. Please try again.', 'error');
                return;
            }

            newRow.dataset.roleId = roleId;
            newRow.dataset.quantity = String(quantity);

            const roleInput = newRow.querySelector('[data-manpower-role-input]');
            const quantityInput = newRow.querySelector('[data-manpower-quantity-input]');
            if (roleInput) roleInput.value = roleId;
            if (quantityInput) quantityInput.value = String(quantity);

            const roleLabelEl = newRow.querySelector('[data-manpower-role-label]');
            const quantityLabelEl = newRow.querySelector('[data-manpower-quantity-label]');
            if (roleLabelEl) roleLabelEl.textContent = roleLabel;
            if (quantityLabelEl) quantityLabelEl.textContent = String(quantity);

            manpowerList?.appendChild(newRow);
            bindManpowerRow(newRow);
        }

        assignManpowerFieldNames();
        emitManpowerUpdated();
        closeManpowerModal();
    };

    const validateManpowerEntries = () => {
        if (!manpowerList) return true;
        const rows = Array.from(manpowerList.querySelectorAll('[data-manpower-entry]'));
        const encounteredRoles = new Set();
        for (const row of rows) {
            const roleInput = row.querySelector('[data-manpower-role-input]');
            const qtyInput = row.querySelector('[data-manpower-quantity-input]');
            const roleValue = roleInput?.value?.trim() || '';
            const quantityValue = qtyInput?.value?.trim() || '';

            if (!roleValue || !quantityValue) {
                window.showToast?.('Please complete each manpower role entry or remove it before continuing.', 'warning');
                return false;
            }

            const quantity = parseInt(quantityValue, 10);
            if (Number.isNaN(quantity) || quantity < 1 || quantity > 99) {
                window.showToast?.('Manpower quantities must be between 1 and 99.', 'warning');
                return false;
            }

            if (encounteredRoles.has(roleValue)) {
                window.showToast?.('Each manpower role can only be added once.', 'warning');
                return false;
            }

            encounteredRoles.add(roleValue);
        }
        return true;
    };

    function bindManpowerRow(row) {
        const editBtn = row.querySelector('[data-edit-manpower]');
        const removeBtn = row.querySelector('[data-remove-manpower]');

        editBtn?.addEventListener('click', () => {
            openManpowerModal({ mode: 'edit', row });
        });

        removeBtn?.addEventListener('click', () => {
            row.remove();
            assignManpowerFieldNames();
            emitManpowerUpdated();
        });
    }

    const requireManpowerCompletion = () => {
        if (!validateManpowerEntries()) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return false;
        }
        return true;
    };

    const setIndicatorState = (index) => {
        indicatorItems.forEach((item) => {
            const stepIndex = parseInt(item.dataset.stepIndex, 10) - 1;
            item.className = 'flex items-center gap-3 rounded-xl border px-4 py-3 text-sm transition';
            const badge = item.querySelector('span');
            if (badge) {
                badge.className = 'flex h-8 w-8 items-center justify-center rounded-full';
            }

            if (stepIndex < index) {
                item.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
                if (badge) badge.classList.add('bg-green-600', 'text-white');
            } else if (stepIndex === index) {
                item.classList.add('border-purple-200', 'bg-purple-50', 'text-purple-700');
                if (badge) badge.classList.add('bg-purple-600', 'text-white');
            } else {
                item.classList.add('border-gray-200', 'bg-white', 'text-gray-500');
                if (badge) badge.classList.add('bg-gray-200', 'text-gray-600');
            }
        });
    };

    const goToStep = (index) => {
        if (index < 0 || index >= steps.length) return;
        currentStep = index;
        steps.forEach((section, idx) => {
            section.classList.toggle('hidden', idx !== currentStep);
        });
        setIndicatorState(index);
        updateSummary();
        
        // Reload calendar when entering Step 2 (Schedule)
        if (index === 1 && typeof window.loadBorrowCalendar === 'function') {
            // Use setTimeout to ensure the DOM is updated
            setTimeout(() => {
                const borrowMonth = window.borrowMonth || new Date().getMonth();
                const borrowYear = window.borrowYear || new Date().getFullYear();
                window.loadBorrowCalendar(null, borrowMonth, borrowYear);
            }, 100);
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const updateStep1NextState = () => {
        if (!step1NextBtn) return;
        const canProceed = locationValid && hasAtLeastOneItem();
        step1NextBtn.disabled = !canProceed;
        step1NextBtn.classList.toggle('opacity-60', !canProceed);
        step1NextBtn.classList.toggle('cursor-not-allowed', !canProceed);
    };

    const updateSummary = () => {
        if (summaryBorrowDates) {
            const borrowText = borrowHidden?.value ? formatSummaryDate(borrowHidden.value) : '--';
            const returnText = returnHidden?.value ? formatSummaryDate(returnHidden.value) : '--';
            summaryBorrowDates.textContent = `${borrowText} -> ${returnText}`;
        }

        if (summaryAddress) {
            summaryAddress.textContent = locationHidden?.value || '--';
        }

        if (summaryManpowerList && summaryManpowerEmpty) {
            const manpowerEntries = getManpowerEntries();
            renderManpowerList(summaryManpowerList, summaryManpowerEmpty, manpowerEntries);
        }

        if (summaryPurposeOffice) {
            const value = purposeOfficeInput?.value?.trim();
            summaryPurposeOffice.textContent = value && value.length ? value : '--';
        }

        if (summaryPurpose) {
            const value = purposeInput?.value?.trim();
            summaryPurpose.textContent = value && value.length ? value : '--';
        }

        if (summaryUsage) {
            summaryUsage.textContent = currentUsageLabel || '--';
        }

        if (summaryItemsList) {
            summaryItemsList.innerHTML = '';
            const items = document.querySelectorAll('[data-item-entry]');
            if (!items.length) {
                const li = document.createElement('li');
                li.textContent = 'No items selected.';
                summaryItemsList.appendChild(li);
            } else {
                items.forEach((item) => {
                    const name = item.dataset.itemName || 'Item';
                    const qty = item.dataset.itemQuantity || '0';
                    const li = document.createElement('li');
                    li.textContent = `${name}(x${qty})`;
                    summaryItemsList.appendChild(li);
                });
            }
        }

        // Update letter file name display using FilePond
        if (letterFileName) {
            const pond = getLetterPond();
            if (pond && pond.getFiles().length > 0) {
                const file = pond.getFiles()[0];
                letterFileName.textContent = file.filename || file.file?.name || 'Letter uploaded';
                letterFileName.classList.remove('hidden');
            } else {
                letterFileName.textContent = '';
                letterFileName.classList.add('hidden');
            }
        }
    };

    const populateModal = () => {
        if (modalBorrowDate) {
            modalBorrowDate.textContent = borrowHidden?.value ? formatSummaryDate(borrowHidden.value) : '--';
        }
        if (modalReturnDate) {
            modalReturnDate.textContent = returnHidden?.value ? formatSummaryDate(returnHidden.value) : '--';
        }
        if (modalAddress) {
            modalAddress.textContent = locationHidden?.value || '--';
        }
        if (modalPurposeOffice) {
            const value = purposeOfficeInput?.value?.trim();
            modalPurposeOffice.textContent = value && value.length ? value : '--';
        }
        if (modalPurpose) {
            const value = purposeInput?.value?.trim();
            modalPurpose.textContent = value && value.length ? value : '--';
        }
        if (modalUsage) {
            modalUsage.textContent = currentUsageLabel || '--';
        }

        if (modalManpowerList && modalManpowerEmpty) {
            const manpowerEntries = getManpowerEntries();
            renderManpowerList(modalManpowerList, modalManpowerEmpty, manpowerEntries);
        }

        if (modalItemsList) {
            modalItemsList.innerHTML = '';
            const items = document.querySelectorAll('[data-item-entry]');
            if (!items.length) {
                const li = document.createElement('li');
                li.textContent = 'No items selected.';
                modalItemsList.appendChild(li);
            } else {
                items.forEach((item) => {
                    const name = item.dataset.itemName || 'Item';
                    const qty = item.dataset.itemQuantity || '0';
                    const li = document.createElement('li');
                    li.textContent = `${name}(x${qty})`;
                    modalItemsList.appendChild(li);
                });
            }
        }

        const modalLetterImage = document.getElementById('modalLetterImage');
        const modalLetterPreviewWrapper = document.getElementById('modalLetterPreviewWrapper');
        const pond = getLetterPond();
        
        if (modalLetterImage && modalLetterPreviewWrapper) {
            // Get file from FilePond
            let file = null;
            if (pond && pond.getFiles().length > 0) {
                file = pond.getFiles()[0].file;
            }
            
            if (file) {
                if (file.type && file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    modalLetterImage.src = url;
                    modalLetterImage.classList.remove('hidden');
                    if (modalLetterName) {
                        modalLetterName.classList.add('hidden');
                    }
                } else {
                    modalLetterImage.classList.add('hidden');
                    if (modalLetterName) {
                        modalLetterName.textContent = file.name || 'Letter uploaded';
                        modalLetterName.classList.remove('hidden');
                    }
                }
            } else {
                modalLetterImage.classList.add('hidden');
                if (modalLetterName) {
                    modalLetterName.textContent = 'No letter uploaded.';
                    modalLetterName.classList.remove('hidden');
                }
            }
        } else if (modalLetterName) {
            if (pond && pond.getFiles().length > 0) {
                const file = pond.getFiles()[0].file;
                modalLetterName.textContent = file?.name || 'Letter uploaded';
            } else {
                modalLetterName.textContent = 'No letter uploaded.';
            }
        }
    };

    if (manpowerList) {
        Array.from(manpowerList.querySelectorAll('[data-manpower-entry]')).forEach((row) => {
            bindManpowerRow(row);
        });
        assignManpowerFieldNames();
        emitManpowerUpdated();
        injectDefaultManpowerIfNeeded();
    }

    if (!manpowerList) {
        defaultManpowerAttempted = true;
    }

    addManpowerBtn?.addEventListener('click', () => {
        if (!manpowerList || !manpowerTemplate || !manpowerModalRoleSelect) return;
        const availableOptions = getRoleOptionsForModal();
        if (!availableOptions.length) {
            window.showToast?.('All available roles have already been added.', 'info');
            return;
        }
        openManpowerModal({ mode: 'add' });
    });

    manpowerModalConfirmBtn?.addEventListener('click', applyManpowerModalSelection);

    const handleModalDismiss = () => {
        closeManpowerModal();
    };

    manpowerModalCancelBtn?.addEventListener('click', handleModalDismiss);
    manpowerModalDismissButtons.forEach((button) => {
        button.addEventListener('click', handleModalDismiss);
    });

    manpowerModalQuantityInput?.addEventListener('input', () => {
        if (!manpowerModalQuantityInput) return;
        const numeric = manpowerModalQuantityInput.value.replace(/[^0-9]/g, '');
        manpowerModalQuantityInput.value = numeric.slice(0, 2);
    });

    manpowerModalQuantityInput?.addEventListener('blur', () => {
        clampManpowerQuantity(manpowerModalQuantityInput);
    });

    if (step1NextBtn) {
        step1NextBtn.addEventListener('click', () => {
            if (!locationValid || !hasAtLeastOneItem()) return;

            const office = (purposeOfficeInput?.value || '').trim();
            const purpose = (purposeInput?.value || '').trim();

            if (!office && !purpose) {
                window.showToast('Please fill in Request Office/Agency and Purpose before proceeding.', 'warning');
                try { purposeOfficeInput?.focus(); } catch (_) { /* no-op */ }
                return;
            }
            if (!office) {
                window.showToast('Please fill in the Request Office/Agency field.', 'warning');
                try { purposeOfficeInput?.focus(); } catch (_) { /* no-op */ }
                return;
            }
            if (!purpose) {
                window.showToast('Please fill in the Purpose field.', 'warning');
                try { purposeInput?.focus(); } catch (_) { /* no-op */ }
                return;
            }

            if (!requireManpowerCompletion()) {
                return;
            }

            goToStep(1);
        });
    }

    if (step2BackBtn) {
        step2BackBtn.addEventListener('click', () => goToStep(0));
    }

    if (step2NextBtn) {
        step2NextBtn.addEventListener('click', () => {
            if (!borrowHidden?.value || !returnHidden?.value) {
                window.showToast('Please select both borrow and return dates before proceeding.', 'warning');
                return;
            }
            if (!requireManpowerCompletion()) {
                goToStep(0);
                return;
            }
            goToStep(2);
        });
    }

    if (step3BackBtn) {
        step3BackBtn.addEventListener('click', () => goToStep(1));
    }

    // Initialize FilePond for letter input
    // FilePond is initialized in item-filepond.js, so we just need to hook into it
    const initializeLetterPond = () => {
        const pond = getLetterPond();
        if (pond) {
            letterPond = pond;
            // Listen for file changes
            pond.on('addfile', () => {
                updateSummary();
            });
            pond.on('removefile', () => {
                updateSummary();
            });
        } else if (window.FilePond || typeof FilePond !== 'undefined') {
            // Retry after a short delay if not found yet
            setTimeout(initializeLetterPond, 100);
        }
    };
    
    if (letterInput) {
        // Try to initialize immediately
        initializeLetterPond();
    }

    if (openConfirmModalBtn) {
        openConfirmModalBtn.addEventListener('click', () => {
            if (!borrowHidden?.value || !returnHidden?.value) {
                window.showToast('Please select valid borrow and return dates.', 'warning');
                goToStep(1);
                return;
            }
            if (!locationHidden?.value) {
                window.showToast('Please complete the address selection before submitting.', 'warning');
                goToStep(0);
                return;
            }

            if (!requireManpowerCompletion()) {
                goToStep(0);
                return;
            }
            
            // Check for file in FilePond
            const pond = getLetterPond();
            const hasFile = pond && pond.getFiles().length > 0;
            
            if (!hasFile) {
                window.showToast('Please upload your letter before submitting.', 'warning');
                return;
            }

            updateSummary();
            populateModal();
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'borrowConfirmModal' }));
        });
    }

    if (confirmBorrowRequestBtn) {
        confirmBorrowRequestBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (!requireManpowerCompletion()) {
                goToStep(0);
                return;
            }

            // Get FilePond instance and check for uploaded file
            const pond = getLetterPond();
            const hasFileInPond = pond && pond.getFiles().length > 0;
            
            if (!hasFileInPond) {
                window.showToast('Please upload your signed letter before proceeding.', 'warning');
                return;
            }
            
            confirmBorrowRequestBtn.disabled = true;
            confirmBorrowRequestBtn.classList.add('opacity-60', 'cursor-not-allowed');
            
            try {
                // Build FormData manually to ensure FilePond file is included
                const formData = new FormData(form);
                
                // Get the file from FilePond and add it to FormData
                // This ensures the file is included even if FilePond hasn't synced to the input
                const files = pond.getFiles();
                if (files.length > 0) {
                    const file = files[0].file;
                    // Remove any existing support_letter entry and add the actual file
                    formData.delete('support_letter');
                    formData.append('support_letter', file, file.name);
                }
                
                // Submit the form using fetch to have full control
                // Note: fetch will follow redirects automatically
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    redirect: 'follow', // Follow redirects
                });
                
                // Get the final URL after redirects
                const finalUrl = response.url || window.location.href;
                const submitUrl = form.action;
                
                // Check if we're still on the submit page (validation error) or redirected (success)
                // Success redirects to /user/borrow-items, error redirects back to /user/borrow-list/submit
                if (finalUrl.includes('/borrow-list/submit')) {
                    // Still on submit page - validation error occurred
                    try {
                        const text = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(text, 'text/html');
                        
                        // Look for error messages in the response
                        const errorElement = doc.querySelector('.alert-error, .text-red-600, [role="alert"], .error, x-alert[type="error"]');
                        if (errorElement) {
                            const errorText = errorElement.textContent || errorElement.innerText || 'Please check your form and try again.';
                            window.showToast(errorText, 'error');
                        } else {
                            window.showToast('Please check your form and try again.', 'error');
                        }
                    } catch (e) {
                        // If we can't parse the response, just show generic error
                        window.showToast('Please check your form and try again.', 'error');
                    }
                    // Reload to show errors on page
                    window.location.reload();
                } else {
                    // Redirected to success page (borrow-items) - success!
                    // Show success toast if available, then redirect
                    try {
                        // Import toast function if available
                        if (typeof showToast === 'function') {
                            showToast('Borrow request submitted successfully!', 'success');
                        } else if (window.showToast && typeof window.showToast === 'function') {
                            window.showToast('Borrow request submitted successfully!', 'success');
                        }
                    } catch (e) {
                        // Toast not available, that's okay - flash message will show
                    }
                    // Follow the redirect
                    window.location.href = finalUrl;
                }
            } catch (error) {
                console.error('Form submission error:', error);
                window.showToast('An error occurred while submitting your request. Please try again.', 'error');
            } finally {
                confirmBorrowRequestBtn.disabled = false;
                confirmBorrowRequestBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    window.addEventListener('location-updated', (event) => {
        locationValid = !!event.detail?.valid;
        updateStep1NextState();
        updateSummary();
    });

    window.addEventListener('borrow:dates-updated', () => {
        updateSummary();
    });

    window.addEventListener('borrow:item-quantity-changed', () => {
        injectDefaultManpowerIfNeeded();
        updateSummary();
        updateStep1NextState();
    });

    window.addEventListener('borrow:manpower-updated', () => {
        updateSummary();
    });

    window.addEventListener('borrow:usage-updated', (event) => {
        currentUsageLabel = event.detail?.label || formatUsageLabel(
            usageStartInput?.value || null,
            usageEndInput?.value || null,
        ) || '--';
        updateSummary();
    });

    if (purposeOfficeInput) {
        purposeOfficeInput.addEventListener('input', updateSummary);
    }

    if (purposeInput) {
        purposeInput.addEventListener('input', updateSummary);
    }

    // FilePond file changes are handled in initializeLetterPond

    updateStep1NextState();
    updateSummary();
    goToStep(0);
});
