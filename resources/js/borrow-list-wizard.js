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
                    li.textContent = `${name} — Qty: ${qty}`;
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
                alert('Please select valid borrow and return dates.');
                goToStep(1);
                return;
            }
            if (!locationHidden?.value) {
                alert('Please complete the address selection before submitting.');
                goToStep(0);
                return;
            }
            
            // Check for file in FilePond
            const pond = getLetterPond();
            const hasFile = pond && pond.getFiles().length > 0;
            
            if (!hasFile) {
                alert('Please upload your letter before submitting.');
                return;
            }

            updateSummary();
            populateModal();
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'borrowConfirmModal' }));
        });
    }

    // Intercept form submission to ensure FilePond file is included
    if (form) {
        form.addEventListener('submit', (e) => {
            const pond = getLetterPond();
            
            // Ensure FilePond file is attached to the input before submission
            if (pond && pond.getFiles().length > 0) {
                const fileItem = pond.getFiles()[0];
                const file = fileItem.file;
                
                // FilePond hides the original input but keeps it in the DOM
                // We need to find the actual input element (it might be hidden)
                let actualInput = letterInput;
                
                // If the input was replaced by FilePond, try to find it
                // FilePond keeps the original input in a hidden state
                if (!actualInput || actualInput.type !== 'file') {
                    // Try to find the input by name or ID
                    actualInput = form.querySelector('input[name="support_letter"]') || 
                                  form.querySelector('input#support_letter');
                }
                
                if (actualInput && file && file instanceof File) {
                    try {
                        // Create a DataTransfer object to set the file
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        
                        // Set the files on the input element
                        // This ensures the file is included in FormData
                        actualInput.files = dataTransfer.files;
                        
                        // Verify the file was set
                        if (actualInput.files.length === 0) {
                            console.warn('FilePond file was not set on input element');
                        }
                    } catch (error) {
                        console.error('Error setting FilePond file on input:', error);
                    }
                }
            }
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

    // FilePond file changes are handled in initializeLetterPond

    updateStep1NextState();
    updateSummary();
    goToStep(0);
});
