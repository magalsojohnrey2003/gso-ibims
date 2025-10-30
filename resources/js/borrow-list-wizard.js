import initLocationSelector from './location-selector';

function formatSummaryDate(dateValue) {
    if (!dateValue) return '—';
    const parsed = new Date(dateValue);
    if (Number.isNaN(parsed.getTime())) return '—';
    return parsed.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
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

    const modalBorrowDate = document.getElementById('modalBorrowDate');
    const modalReturnDate = document.getElementById('modalReturnDate');
    const modalItemsList = document.getElementById('modalItemsList');
    const modalAddress = document.getElementById('modalAddress');
    const modalLetterName = document.getElementById('modalLetterName');

    const borrowHidden = document.getElementById('borrow_date');
    const returnHidden = document.getElementById('return_date');
    const locationHidden = document.getElementById('location');
    const manpowerInput = document.getElementById('manpower_count');

    let currentStep = 0;
    let locationValid = !!(locationHidden?.value);

    initLocationSelector({
        barangaysUrl: window.LOCATION_ENDPOINTS?.barangays,
        puroksUrl: window.LOCATION_ENDPOINTS?.puroks
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
                if (badge) {
                    badge.classList.add('bg-green-600', 'text-white');
                }
            } else if (stepIndex === index) {
                item.classList.add('border-purple-200', 'bg-purple-50', 'text-purple-700');
                if (badge) {
                    badge.classList.add('bg-purple-600', 'text-white');
                }
            } else {
                item.classList.add('border-gray-200', 'bg-white', 'text-gray-500');
                if (badge) {
                    badge.classList.add('bg-gray-200', 'text-gray-600');
                }
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
        step1NextBtn.disabled = !locationValid;
        step1NextBtn.classList.toggle('opacity-60', !locationValid);
        step1NextBtn.classList.toggle('cursor-not-allowed', !locationValid);
    };

    const updateSummary = () => {
        if (summaryBorrowDates) {
            const borrowText = borrowHidden?.value ? formatSummaryDate(borrowHidden.value) : '—';
            const returnText = returnHidden?.value ? formatSummaryDate(returnHidden.value) : '—';
            summaryBorrowDates.textContent = `${borrowText} → ${returnText}`;
        }

        if (summaryAddress) {
            summaryAddress.textContent = locationHidden?.value || '—';
        }

        if (summaryManpower) {
            const value = manpowerInput?.value;
            summaryManpower.textContent = value ? `${value} personnel` : 'No additional manpower requested';
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
            modalBorrowDate.textContent = borrowHidden?.value ? formatSummaryDate(borrowHidden.value) : '—';
        }
        if (modalReturnDate) {
            modalReturnDate.textContent = returnHidden?.value ? formatSummaryDate(returnHidden.value) : '—';
        }
        if (modalAddress) {
            modalAddress.textContent = locationHidden?.value || '—';
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
            if (!locationValid) return;
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
                if (typeof window.injectBorrowRoles === 'function') {
                    window.injectBorrowRoles(form);
                }
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

    if (manpowerInput) {
        manpowerInput.addEventListener('input', updateSummary);
    }

    updateStep1NextState();
    updateSummary();
    goToStep(0);
});
