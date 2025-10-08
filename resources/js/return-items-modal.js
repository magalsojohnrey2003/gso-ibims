const STEP_SELECTION = 'selection';
const STEP_SUMMARY = 'summary';

let stepContainers = {};
let stepperElements = [];
let nextButton = null;
let backButton = null;

function setStep(step) {
    Object.entries(stepContainers).forEach(([name, el]) => {
        if (name === step) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });

    stepperElements.forEach((el) => {
        const label = el.getAttribute('data-step-label');
        if (label === step) {
            el.classList.add('return-step-active');
        } else {
            el.classList.remove('return-step-active');
        }
    });

    if (backButton) {
        backButton.classList.toggle('hidden', step === STEP_SELECTION);
    }
}

export function initReturnItemsWizard(callbacks = {}) {
    const forms = document.querySelectorAll('[data-return-step]');
    stepContainers = {};
    forms.forEach((el) => {
        const key = el.getAttribute('data-return-step');
        if (key) {
            stepContainers[key] = el;
        }
    });

    stepperElements = Array.from(document.querySelectorAll('[data-return-stepper] .return-step'));

    nextButton = document.getElementById('returnWizardNextBtn');
    if (nextButton) {
        nextButton.addEventListener('click', async () => {
            if (callbacks.onNext) {
                await callbacks.onNext();
            }
        });
    }

    backButton = document.getElementById('returnWizardBackBtn');
    if (backButton) {
        backButton.addEventListener('click', () => {
            if (callbacks.onBack) {
                callbacks.onBack();
            }
        });
    }

    setStep(STEP_SELECTION);
}

export function goToSummaryStep() {
    setStep(STEP_SUMMARY);
}

export function goToSelectionStep() {
    setStep(STEP_SELECTION);
}

export function setNextDisabled(disabled) {
    if (nextButton) {
        nextButton.disabled = disabled;
    }
}
