const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

document.addEventListener('DOMContentLoaded', () => {
  const buttons = Array.from(document.querySelectorAll('[data-print-stickers]'));
  const modal = document.querySelector('[data-print-modal]');
  if (!buttons.length || !modal || !csrfToken) return;

  const form = modal.querySelector('[data-print-form]');
  const summaryEl = modal.querySelector('[data-print-summary]');
  const routeInput = modal.querySelector('[data-print-route-input]');
  const quantityInput = modal.querySelector('[data-print-quantity-input]');
  const personInput = modal.querySelector('[data-print-person]');
  const orientationSelect = modal.querySelector('[data-print-orientation]');
  const canvas = modal.querySelector('[data-print-signature-canvas]');
  const clearBtn = modal.querySelector('[data-print-signature-clear]');
  const cancelBtn = modal.querySelector('[data-print-cancel]');

  if (!form || !summaryEl || !routeInput || !quantityInput || !personInput || !orientationSelect || !canvas || !clearBtn || !cancelBtn) {
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  let drawing = false;
  let hasSignature = false;
  const PEN_WIDTH = 3.5;
  const state = {
    route: '',
    quantity: 1,
    item: '',
    acquisition: '',
  };

  const applyPenStyle = () => {
    ctx.lineWidth = PEN_WIDTH;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#1f2937';
  };

  const setCanvasDimensions = () => {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    const fallbackWidth = 480;
    const fallbackHeight = 180;
    const displayWidth = rect.width > 1 ? rect.width : fallbackWidth;
    const displayHeight = rect.height > 1 ? rect.height : fallbackHeight;
    canvas.width = displayWidth * ratio;
    canvas.height = displayHeight * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    applyPenStyle();
    clearSignature();
  };

  const clearSignature = () => {
    const { width, height } = canvas;
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    ctx.restore();
    ctx.beginPath();
    applyPenStyle();
    hasSignature = false;
  };

  const getPoint = (event) => {
    const rect = canvas.getBoundingClientRect();
    const pointer = event.touches ? event.touches[0] : event;
    return {
      x: pointer.clientX - rect.left,
      y: pointer.clientY - rect.top,
    };
  };

  const startSignature = (event) => {
    event.preventDefault();
    drawing = true;
    const { x, y } = getPoint(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
  };

  const moveSignature = (event) => {
    if (!drawing) return;
    event.preventDefault();
    const { x, y } = getPoint(event);
    ctx.lineTo(x, y);
    ctx.stroke();
    hasSignature = true;
  };

  const endSignature = (event) => {
    if (!drawing) return;
    event.preventDefault();
    drawing = false;
    ctx.beginPath();
  };

  canvas.style.touchAction = 'none';
  canvas.style.cursor = 'crosshair';
  canvas.style.border = '1px solid #d1d5db';
  canvas.style.backgroundColor = '#ffffff';
  setCanvasDimensions();
  window.addEventListener('resize', setCanvasDimensions);

  canvas.addEventListener('pointerdown', startSignature);
  canvas.addEventListener('pointermove', moveSignature);
  canvas.addEventListener('pointerup', endSignature);
  canvas.addEventListener('pointerleave', endSignature);
  canvas.addEventListener('pointercancel', endSignature);

  clearBtn.addEventListener('click', (event) => {
    event.preventDefault();
    clearSignature();
  });

  cancelBtn.addEventListener('click', (event) => {
    event.preventDefault();
    clearSignature();
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'print-stickers' }));
  });

  const openModal = (config) => {
    state.route = config.route;
    state.quantity = config.quantity;
    state.item = config.item || 'this item';
    state.acquisition = config.acquisition || '';

    routeInput.value = state.route;
    quantityInput.value = String(state.quantity);
    personInput.value = '';
    clearSignature();

    const parts = [`${state.quantity} sticker${state.quantity === 1 ? '' : 's'} will be generated.`];
    parts.unshift(`Printing for "${state.item}"`);
    if (state.acquisition) {
      parts.push(`Acquisition date: ${state.acquisition}`);
    }
    summaryEl.textContent = parts.join(' | ');

    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'print-stickers' }));
    requestAnimationFrame(() => {
      setCanvasDimensions();
    });
  };

  buttons.forEach((button) => {
    if (button.dataset.printBound === '1') return;
    button.dataset.printBound = '1';

    button.addEventListener('click', () => {
      const route = button.dataset.printRoute;
      if (!route) return;

      const quantity = Number.parseInt(button.dataset.printQuantity || button.dataset.printDefault || '1', 10);
      const itemName = button.dataset.printItem || '';
      const acquisition = button.dataset.printAcquisition || '';

      openModal({
        route,
        quantity: Number.isFinite(quantity) && quantity > 0 ? quantity : 1,
        item: itemName,
        acquisition,
      });
    });
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();

    const route = routeInput.value;
    if (!route) return;

    const quantity = Number.parseInt(quantityInput.value || '1', 10) || 1;
    const person = personInput.value.trim();
    let signatureData = '';

    if (hasSignature) {
      try {
        const candidate = canvas.toDataURL('image/png');
        if (candidate && candidate.startsWith('data:image')) {
          signatureData = candidate;
        }
      } catch (error) {
        console.warn('Failed to export signature:', error);
      }
    }

    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = route;
    tempForm.target = '_blank';

    const appendField = (name, value) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      tempForm.appendChild(input);
    };

    appendField('_token', csrfToken);
    appendField('person_accountable', person);
    if (!signatureData) {
      signatureData = ' ';
    }
    appendField('signature_data', signatureData);
    appendField('quantity', String(quantity));
    appendField('orientation', orientationSelect.value || 'P');

    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);

    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'print-stickers' }));
    clearSignature();
  });
});

