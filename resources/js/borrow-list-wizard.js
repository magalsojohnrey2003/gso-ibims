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

    // FilePond file changes are handled in initializeLetterPond

    updateStep1NextState();
    updateSummary();
    goToStep(0);
});
