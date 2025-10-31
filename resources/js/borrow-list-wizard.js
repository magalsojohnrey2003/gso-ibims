import initLocationSelector from './location-selector';

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

    const summaryBorrowDates = document.getElementById('summaryBorrowDates');
    const summaryAddress = document.getElementById('summaryAddress');
    const summaryManpower = document.getElementById('summaryManpower');
    const summaryItemsList = document.getElementById('summaryItemsList');
    const summaryPurposeOffice = document.getElementById('summaryPurposeOffice');
    const summaryPurpose = document.getElementById('summaryPurpose');
    const summaryUsage = document.getElementById('summaryUsage');

    const modalBorrowDate = document.getElementById('modalBorrowDate');
    const modalReturnDate = document.getElementById('modalReturnDate');
    const modalItemsList = document.getElementById('modalItemsList');
    const modalAddress = document.getElementById('modalAddress');
    const modalLetterName = document.getElementById('modalLetterName');
    const modalPurposeOffice = document.getElementById('modalPurposeOffice');
    const modalPurpose = document.getElementById('modalPurpose');
    const modalUsage = document.getElementById('modalUsage');

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const locationHidden = document.getElementById('location');
    const manpowerInput = document.getElementById('manpower_count');
    const purposeOfficeInput = document.getElementById('purpose_office');
    const purposeInput = document.getElementById('purpose');
    const usageStartSelect = document.getElementById('usage_start');
    const usageEndSelect = document.getElementById('usage_end');

    let currentStep = 0;
    let locationValid = !!(locationHidden?.value);
    const hasAtLeastOneItem = () => document.querySelectorAll('#borrowListItems [data-item-entry]').length > 0;
    let currentUsageLabel = formatUsageLabel(
        usageStartSelect?.value || null,
        usageEndSelect?.value || null,
    ) || '--';

    initLocationSelector({
        barangaysUrl: window.LOCATION_ENDPOINTS?.barangays,
        puroksUrl: window.LOCATION_ENDPOINTS?.puroks,
    });

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

        if (summaryManpower) {
            const value = manpowerInput?.value;
            summaryManpower.textContent = value ? `${value} personnel` : 'No additional manpower requested';
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
                    li.textContent = `${name} — Qty: ${qty}`;
                    summaryItemsList.appendChild(li);
                });
            }
        }

        if (letterFileName && letterInput?.files?.length) {
            letterFileName.textContent = letterInput.files[0].name;
            letterFileName.classList.remove('hidden');
        } else if (letterFileName) {
            letterFileName.textContent = '';
            letterFileName.classList.add('hidden');
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
                    li.textContent = `${name} — Qty: ${qty}`;
                    modalItemsList.appendChild(li);
                });
            }
        }

        if (modalLetterName) {
            if (letterInput?.files?.length) {
                modalLetterName.textContent = letterInput.files[0].name;
            } else {
                modalLetterName.textContent = 'No letter uploaded.';
            }
        }
    };

    if (step1NextBtn) {
        step1NextBtn.addEventListener('click', () => {
            if (!locationValid || !hasAtLeastOneItem()) return;
            goToStep(1);
        });
    }

    if (step2BackBtn) {
        step2BackBtn.addEventListener('click', () => goToStep(0));
    }

    if (step2NextBtn) {
        step2NextBtn.addEventListener('click', () => {
            if (!borrowHidden?.value || !returnHidden?.value) {
                alert('Please select both borrow and return dates before proceeding.');
                return;
            }
            goToStep(2);
        });
    }

    if (step3BackBtn) {
        step3BackBtn.addEventListener('click', () => goToStep(1));
    }

    if (letterInput) {
        letterInput.addEventListener('change', updateSummary);
    }

    if (openConfirmModalBtn) {
        openConfirmModalBtn.addEventListener('click', () => {
            if (!borrowHidden?.value || !returnHidden?.value) {
                alert('Please select valid borrow and return dates.');
                goToStep(1);
                return;
            }
            if (!locationHidden?.value) {
                alert('Please complete the address selection before submitting.');
                goToStep(0);
                return;
            }
            if (!letterInput?.files?.length) {
                alert('Please upload your letter before submitting.');
                return;
            }

            updateSummary();
            populateModal();
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'borrowConfirmModal' }));
        });
    }

    if (confirmBorrowRequestBtn) {
        confirmBorrowRequestBtn.addEventListener('click', () => {
            confirmBorrowRequestBtn.disabled = true;
            confirmBorrowRequestBtn.classList.add('opacity-60', 'cursor-not-allowed');
            try {
                form.submit();
            } finally {
                setTimeout(() => {
                    confirmBorrowRequestBtn.disabled = false;
                    confirmBorrowRequestBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                }, 1500);
            }
        });
    }

    window.addEventListener('location:updated', (event) => {
        locationValid = !!event.detail?.valid;
        updateStep1NextState();
        updateSummary();
    });

    window.addEventListener('borrow:dates-updated', () => {
        updateSummary();
    });

    window.addEventListener('borrow:item-quantity-changed', () => {
        updateSummary();
        updateStep1NextState();
    });

    window.addEventListener('borrow:usage-updated', (event) => {
        currentUsageLabel = event.detail?.label || formatUsageLabel(
            usageStartSelect?.value || null,
            usageEndSelect?.value || null,
        ) || '--';
        updateSummary();
    });

    if (manpowerInput) {
        manpowerInput.addEventListener('input', updateSummary);
    }

    if (purposeOfficeInput) {
        purposeOfficeInput.addEventListener('input', updateSummary);
    }

    if (purposeInput) {
        purposeInput.addEventListener('input', updateSummary);
    }

    if (letterInput) {
        letterInput.addEventListener('change', updateSummary);
    }

    updateStep1NextState();
    updateSummary();
    goToStep(0);
});
