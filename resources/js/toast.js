const DEFAULT_DURATION = 4000;
const ICONS = {
    success: '\u2713',
    error: '\u26A0',
    info: '\u2139',
};

function ensureContainer() {
    let container = document.getElementById('toast-root');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-root';
        container.className = 'pointer-events-none fixed top-6 right-6 z-[9999] flex flex-col gap-3';
        document.body.appendChild(container);
    }
    return container;
}

function createToastElement(type, message) {
    const container = ensureContainer();
    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto w-80 max-w-sm rounded-xl bg-slate-900/95 text-white shadow-lg ring-1 ring-black/5 backdrop-blur px-4 py-3 flex items-start gap-3 transition transform duration-150 ease-out opacity-0 translate-y-2';

    const iconWrap = document.createElement('span');
    iconWrap.className = 'mt-0.5 text-xl';
    iconWrap.textContent = ICONS[type] || ICONS.info;

    const textWrap = document.createElement('div');
    textWrap.className = 'text-sm leading-snug flex-1';
    textWrap.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'ml-3 text-white/60 hover:text-white focus:outline-none';
    closeBtn.setAttribute('aria-label', 'Dismiss notification');
    closeBtn.innerHTML = '&times;';

    const remove = () => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 120);
    };

    closeBtn.addEventListener('click', remove);
    toast.addEventListener('mouseenter', () => {
        if (toast.__timer) {
            clearTimeout(toast.__timer);
            toast.__timer = null;
        }
    });
    toast.addEventListener('mouseleave', () => {
        if (!toast.__timer) {
            toast.__timer = setTimeout(remove, DEFAULT_DURATION);
        }
    });

    toast.appendChild(iconWrap);
    toast.appendChild(textWrap);
    toast.appendChild(closeBtn);

    container.appendChild(toast);
    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-2');
    });
    toast.__timer = setTimeout(remove, DEFAULT_DURATION);
    return toast;
}

export default function showToast(type = 'info', message = '') {
    if (typeof document === 'undefined') {
        return;
    }
    const safeType = ['success', 'error', 'info'].includes(type) ? type : 'info';
    const trimmed = String(message || '').trim();
    const displayMessage = trimmed.length ? trimmed : 'Notification';
    createToastElement(safeType, displayMessage);
}

if (typeof window !== 'undefined') {
    window.showToast = showToast;
}

