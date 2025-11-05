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

function looksLikeImageFile(file, fallbackName = '') {
    const mime = String(file?.type || '').toLowerCase();
    if (mime.startsWith('image/')) return true;
    const name = String(file?.name || fallbackName || '').toLowerCase();
    return /\.(png|jpe?g|gif|webp|bmp)$/i.test(name);
}

function extractFilePondPreviewUrl(input) {
    if (!input) return null;
    const root = input.parentElement;
    if (!root) return null;
    const previewEl = root.querySelector('.filepond--image-preview');
    if (!previewEl) return null;

    const styleValue = previewEl.style?.backgroundImage || '';
    const match = styleValue.match(/url\((['"]?)(.*?)\1\)/i);
    if (match && match[2]) return match[2];

    const imgEl = previewEl.querySelector('img');
    if (imgEl?.src) return imgEl.src;

    const canvasEl = previewEl.querySelector('canvas');
    if (canvasEl && typeof canvasEl.toDataURL === 'function') {
        try {
            return canvasEl.toDataURL('image/png');
        } catch (error) {
            console.warn('Failed to extract canvas preview for support letter', error);
        }
    }

    return null;
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

    function handleLetterPondFileChange() {
        if (letterInput) {
            try {
                letterInput.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (error) {
                console.warn('Failed to dispatch change event on support_letter input', error);
            }
        }
        updateSummary();
    }

    function resolveLetterPondInstance() {
        if (!letterInput || typeof FilePond === 'undefined') return null;
        if (letterPond && typeof letterPond.getFiles === 'function') return letterPond;

        let instance = letterInput._pond || letterInput.filepond || null;

        if (!instance && typeof FilePond.find === 'function') {
            try {
                instance = FilePond.find(letterInput);
            } catch (err) {
                const root = letterInput.parentElement?.querySelector('.filepond--root');
                if (root) {
                    try {
                        instance = FilePond.find(root);
                    } catch (innerErr) {
                        console.warn('Unable to resolve FilePond instance for support letter', innerErr);
                    }
                }
            }
        }

        if (instance && typeof instance.on === 'function' && !instance.__borrowWizardListenerAttached) {
            instance.on('addfile', handleLetterPondFileChange);
            instance.on('removefile', handleLetterPondFileChange);
            instance.__borrowWizardListenerAttached = true;
        }

        if (instance) {
            letterPond = instance;
        }

        return letterPond;
    }

    function scheduleLetterPondResolution() {
        if (!letterInput || typeof FilePond === 'undefined') return;
        if (resolveLetterPondInstance()) {
            scheduleLetterPondResolution.attempts = 0;
            return;
        }

        scheduleLetterPondResolution.attempts = (scheduleLetterPondResolution.attempts || 0) + 1;
        if (scheduleLetterPondResolution.attempts > 20) return;

        setTimeout(scheduleLetterPondResolution, 150);
    }

    function getLetterFileInfo() {
        const pond = resolveLetterPondInstance();
        if (pond && typeof pond.getFiles === 'function') {
            const files = pond.getFiles();
            if (files && files.length > 0) {
                const primary = files[0];
                const file = primary?.file || null;
                const name = primary?.filename || primary?.file?.name || '';
                return { file, name };
            }
        }

        const datasetName = letterInput?.dataset?.filepondFileName || '';
        const datasetHasFile = letterInput?.dataset?.filepondHasFile === '1';

        if (letterInput?.files?.length) {
            const file = letterInput.files[0];
            return { file, name: file?.name || datasetName };
        }

        if (datasetHasFile) {
            return { file: null, name: datasetName };
        }

        const pondList = letterInput?.parentElement?.querySelector('.filepond--list');
        const pondItem = pondList?.querySelector('.filepond--item:not(.filepond--item-error)');
        if (pondItem) {
            const label = pondItem.querySelector('.filepond--file-info-main')?.textContent || '';
            return { file: null, name: label?.trim() || datasetName };
        }

        return { file: null, name: '' };
    }

    function hasLetterUpload() {
        const { file, name } = getLetterFileInfo();
        if (file) return true;
        if (name && name.trim().length > 0) return true;
        if (letterInput?.dataset?.filepondHasFile === '1') return true;
        const pondList = letterInput?.parentElement?.querySelector('.filepond--list');
        if (pondList?.querySelector('.filepond--item:not(.filepond--item-error)')) return true;
        return false;
    }

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
    const modalLetterLink = document.getElementById('modalLetterLink');
    const modalLetterDetails = document.getElementById('modalLetterDetails');
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
    let currentLetterObjectUrl = null;
    let letterObjectUrlActive = false;

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

        if (letterFileName) {
            const letterInfo = getLetterFileInfo();
            if (letterInfo.name) {
                letterFileName.textContent = letterInfo.name;
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

        const { file: letterFile, name: letterName } = getLetterFileInfo();
        const modalLetterImage = document.getElementById('modalLetterImage');
        const modalLetterPreviewWrapper = document.getElementById('modalLetterPreviewWrapper');

        if (letterObjectUrlActive && currentLetterObjectUrl) {
            try {
                URL.revokeObjectURL(currentLetterObjectUrl);
            } catch (revokeError) {
                console.warn('Failed to revoke previous letter preview URL', revokeError);
            }
            currentLetterObjectUrl = null;
            letterObjectUrlActive = false;
        }

        let previewUrl = null;
        if (letterFile && typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
            try {
                previewUrl = URL.createObjectURL(letterFile);
                currentLetterObjectUrl = previewUrl;
                letterObjectUrlActive = true;
            } catch (previewError) {
                console.warn('Failed to generate preview URL for support letter', previewError);
            }
        }

        if (!previewUrl) {
            const fallbackPreview = extractFilePondPreviewUrl(letterInput);
            if (fallbackPreview) {
                previewUrl = fallbackPreview;
            }
        }

        const imageCandidate = looksLikeImageFile(letterFile, letterName);
        const showImagePreview = Boolean(imageCandidate && previewUrl);
        if (modalLetterImage) {
            if (showImagePreview) {
                modalLetterImage.src = previewUrl;
                modalLetterImage.classList.remove('hidden');
                modalLetterImage.alt = letterName ? `Preview of ${letterName}` : 'Uploaded letter preview';
            } else {
                modalLetterImage.removeAttribute('src');
                modalLetterImage.classList.add('hidden');
                modalLetterImage.alt = 'Uploaded letter preview';
            }
        }

        if (modalLetterName) {
            modalLetterName.textContent = letterName || (letterFile ? 'Letter uploaded' : 'No letter uploaded');
        }

        if (modalLetterDetails) {
            modalLetterDetails.classList.remove('hidden');
        }

        if (modalLetterLink) {
            if (previewUrl) {
                modalLetterLink.href = previewUrl;
                modalLetterLink.classList.remove('hidden');
            } else {
                modalLetterLink.href = '#';
                modalLetterLink.classList.add('hidden');
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

    if (letterInput && typeof FilePond !== 'undefined') {
        scheduleLetterPondResolution();
        letterInput.addEventListener('change', updateSummary);
    } else if (letterInput) {
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
            
            if (!hasLetterUpload()) {
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
            if (form.dataset.submitting === '1') return;
            form.dataset.submitting = '1';

            confirmBorrowRequestBtn.disabled = true;
            confirmBorrowRequestBtn.classList.add('opacity-60', 'cursor-not-allowed');

            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'borrowConfirmModal' }));

            try {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            } finally {
                setTimeout(() => {
                    form.dataset.submitting = '0';
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
