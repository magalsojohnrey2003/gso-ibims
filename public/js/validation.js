// validation.js — validation + modern body-attached tooltip
document.addEventListener('DOMContentLoaded', function () {
    // ----------------------
    // Helpers: regex & utils
    // ----------------------
    var isValidEmail = function (email) {
        var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    };

    // Phone: digits only, 7-11
    var isValidPhone = function (phone) {
        return (/^\d{7,11}$/).test(phone);
    };

    var isValidName = function (name) { return (/^[A-Za-z\s-]+$/).test(name); };
    var isStrongPassword = function (password) {
        return (/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>_\-]).{8,}$/).test(password);
    };

    // Format phone for humans (0917-123-4567)
    function formatPhoneForDisplay(digits) {
        digits = (digits || '').toString().replace(/\D/g, '').slice(0, 11);
        if (!digits) return '';
        if (digits.length <= 4) return digits;
        if (digits.length <= 7) return digits.slice(0, 4) + '-' + digits.slice(4);
        return digits.slice(0, 4) + '-' + digits.slice(4, 7) + '-' + digits.slice(7);
    }

    // ----------------------
    // Tooltip plumbing
    // ----------------------
    var tooltipEl = null;
    var tooltipVisibleFor = null;
    var repositionRaf = null;

    function ensureTooltip() {
        if (tooltipEl) return tooltipEl;
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'gso-tooltip';
        tooltipEl.className = 'gso-tooltip';
        tooltipEl.setAttribute('role', 'tooltip');
        tooltipEl.setAttribute('aria-hidden', 'true');
        document.body.appendChild(tooltipEl);

        tooltipEl.addEventListener('mouseenter', function () {
            tooltipEl.classList.add('visible');
        });
        tooltipEl.addEventListener('mouseleave', function () {
            hideTooltip();
        });

        return tooltipEl;
    }

    function showTooltip(forEl) {
        if (!forEl) return;
        var message = forEl.getAttribute('data-full-error') || forEl.textContent || '';
        if (!message) return;

        var t = ensureTooltip();
        t.textContent = message;
        t.setAttribute('aria-hidden', 'false');

        // associate for screen readers
        var field = forEl.closest ? forEl.closest('.input-field') : null;
        var relatedInput = field ? field.querySelector('input, textarea, select') : null;
        if (relatedInput) {
            relatedInput.setAttribute('aria-describedby', t.id);
        }

        // measure and position (off-screen then place)
        t.style.left = '0px';
        t.style.top = '-9999px';
        t.classList.add('visible');
        t.style.opacity = '0';
        t.style.pointerEvents = 'none';

        requestAnimationFrame(function () {
            var rect = forEl.getBoundingClientRect();
            var ttRect = t.getBoundingClientRect();
            var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
            var scrollY = window.pageYOffset || document.documentElement.scrollTop;

            var centerX = rect.left + rect.width / 2;
            var left = Math.round(centerX - ttRect.width / 2);
            var margin = 8;
            if (left < margin) left = margin;
            if (left + ttRect.width + margin > window.innerWidth) left = Math.max(margin, window.innerWidth - ttRect.width - margin);

            var top = Math.round(rect.top + scrollY - ttRect.height - 10);
            t.classList.remove('gso-tooltip--bottom', 'gso-tooltip--top');
            if (top < (scrollY + 6)) {
                top = Math.round(rect.bottom + scrollY + 10);
                t.classList.add('gso-tooltip--bottom');
            } else {
                t.classList.add('gso-tooltip--top');
            }

            t.style.left = (left + scrollX) + 'px';
            t.style.top = top + 'px';

            requestAnimationFrame(function () {
                t.style.opacity = '1';
                t.style.pointerEvents = 'auto';
                t.setAttribute('aria-hidden', 'false');
                tooltipVisibleFor = forEl;
            });

            // reposition while scrolling/resizing
            var scheduleReposition = function () {
                if (repositionRaf) cancelAnimationFrame(repositionRaf);
                repositionRaf = requestAnimationFrame(function () {
                    if (tooltipVisibleFor) showTooltip(tooltipVisibleFor);
                });
            };
            window.addEventListener('scroll', scheduleReposition, { passive: true });
            window.addEventListener('resize', scheduleReposition);
        });
    }

    function hideTooltip() {
        if (!tooltipEl) return;
        tooltipEl.style.opacity = '0';
        tooltipEl.style.pointerEvents = 'none';
        tooltipEl.setAttribute('aria-hidden', 'true');

        if (tooltipVisibleFor) {
            var field = tooltipVisibleFor.closest ? tooltipVisibleFor.closest('.input-field') : null;
            var relatedInput = field ? field.querySelector('input, textarea, select') : null;
            if (relatedInput && relatedInput.getAttribute('aria-describedby') === tooltipEl.id) {
                relatedInput.removeAttribute('aria-describedby');
            }
        }

        tooltipVisibleFor = null;

        setTimeout(function () {
            if (tooltipEl) tooltipEl.classList.remove('visible', 'gso-tooltip--top', 'gso-tooltip--bottom');
        }, 160);
    }

    function attachTooltipListeners(errEl) {
        if (!errEl || errEl._gso_tooltip_bound) return;
        errEl._gso_tooltip_bound = true;

        errEl.addEventListener('mouseenter', function () { showTooltip(errEl); });
        errEl.addEventListener('mouseleave', function () { hideTooltip(); });

        errEl.addEventListener('focus', function () { showTooltip(errEl); });
        errEl.addEventListener('blur', function () { hideTooltip(); });

        errEl.addEventListener('touchstart', function (e) {
            e.preventDefault();
            showTooltip(errEl);
        }, { passive: false });

        errEl.addEventListener('touchend', function () {
            setTimeout(function () { hideTooltip(); }, 1500);
        }, { passive: true });

        document.addEventListener('click', function (ev) {
            if (!errEl) return;
            if (tooltipEl && tooltipEl.contains && tooltipEl.contains(ev.target)) return;
            if (errEl.contains && errEl.contains(ev.target)) return;
            hideTooltip();
        });
    }

    // ----------------------
    // Utility: clear UI state of a form
    // ----------------------
    function clearFormUI(form) {
        if (!form) return;
        // inputs: remove has-value, aria-invalid, dataset.cleanValue, aria-describedby, is-valid/is-invalid
        var inputs = form.querySelectorAll('input, textarea, select');
        for (var i = 0; i < inputs.length; i += 1) {
            var inp = inputs[i];
            try { inp.classList.remove('has-value'); } catch (e) {}
            try { inp.classList.remove('is-valid'); } catch (e) {}
            try { inp.classList.remove('is-invalid'); } catch (e) {}
            try { inp.removeAttribute('aria-invalid'); } catch (e) {}
            try {
                if (inp.dataset && typeof inp.dataset.cleanValue !== 'undefined') {
                    try { delete inp.dataset.cleanValue; } catch (e) { inp.dataset.cleanValue = ''; }
                }
            } catch (e) {}
            try {
                if (inp.getAttribute('aria-describedby')) inp.removeAttribute('aria-describedby');
            } catch (e) {}
        }

        // .input-field wrappers: remove error/success classes and clear inline error elements
        var fields = form.querySelectorAll('.input-field');
        for (var f = 0; f < fields.length; f += 1) {
            var fld = fields[f];
            try { fld.classList.remove('error'); } catch (e) {}
            try { fld.classList.remove('success'); } catch (e) {}
            var err = fld.querySelector('.error');
            if (err) {
                try { err.innerText = ''; } catch (e) {}
                try { err.removeAttribute('data-full-error'); } catch (e) {}
                try { err.removeAttribute('tabindex'); } catch (e) {}
                try { err.removeAttribute('data-bag'); } catch (e) {}
            }
        }

        // hide tooltip if visible
        try { hideTooltip(); } catch (e) {}
    }

    // attach to global for inline usage
    try { window.gsoClearFormUI = clearFormUI; } catch (e) {}

    // ----------------------
    // Ensure server-rendered .error nodes are tooltip-ready
    // - now respects data-bag and visible panel
    // ----------------------
    var serverErrors = document.querySelectorAll('.input-field .error');
    for (var si = 0; si < serverErrors.length; si += 1) {
        var errEl = serverErrors[si];

        // read bag attribute if provided
        var bag = null;
        try { bag = errEl.getAttribute ? errEl.getAttribute('data-bag') : null; } catch (e) { bag = null; }

        // Skip error nodes that are inside an inert/hidden auth panel — avoids confusing tooltips
        try {
            var errPanel = errEl.closest ? errEl.closest('.auth-panel') : null;
            if (errPanel && (errPanel.hidden || (errPanel.hasAttribute && errPanel.hasAttribute('inert')))) {
                // do not attach tooltip listeners for hidden panels
                continue;
            }

            // If bag is present and doesn't match the panel type, skip.
            if (bag && errPanel) {
                var panelType = errPanel.classList.contains('login-panel') ? 'login' : (errPanel.classList.contains('register-panel') ? 'register' : null);
                if (panelType && panelType !== bag) {
                    continue;
                }
            }
        } catch (e) {}

        var text = (errEl.textContent || '').trim();
        if (!errEl.hasAttribute('data-full-error')) errEl.setAttribute('data-full-error', text);
        if (!errEl.hasAttribute('tabindex')) errEl.setAttribute('tabindex', '0');

        attachTooltipListeners(errEl);
    }

    // ----------------------
    // UI helpers: setError / setSuccess
    // ----------------------
    var setError = function (element, message) {
        if (!element) return;
        var inputControl = element.closest ? element.closest('.input-field') : element.parentElement;
        if (inputControl) {
            var errorDisplay = inputControl.querySelector(".error");
            if (!errorDisplay) {
                errorDisplay = document.createElement("div");
                errorDisplay.className = "error";
                errorDisplay.setAttribute('tabindex', '0');
                inputControl.appendChild(errorDisplay);
            }
            errorDisplay.innerText = message || '';
            errorDisplay.setAttribute('data-full-error', message || '');
            // If caller passed element.dataset.bag we preserve it, otherwise no bag added here
            if (element && element.dataset && element.dataset.bag) {
                try { errorDisplay.setAttribute('data-bag', element.dataset.bag); } catch (e) {}
            }
            // avoid native title so only custom tooltip appears
            inputControl.classList.add("error");
            inputControl.classList.remove("success");
            try { element.setAttribute('aria-invalid', 'true'); } catch (e) {}
            // defensive input classes for other UI frameworks
            try { element.classList.add('is-invalid'); } catch (e) {}

            attachTooltipListeners(errorDisplay);
        } else {
            try { element.style.borderColor = 'var(--error)'; } catch (e) {}
            try { element.setAttribute('aria-invalid', 'true'); } catch (e) {}
            var next = element.nextElementSibling;
            if (!next || !next.classList.contains('error')) {
                var err = document.createElement('div');
                err.className = 'error';
                err.innerText = message || '';
                err.setAttribute('data-full-error', message || '');
                err.setAttribute('tabindex', '0');
                if (element.parentNode) element.parentNode.insertBefore(err, element.nextSibling);
                attachTooltipListeners(err);
            } else {
                next.innerText = message || '';
                next.setAttribute('data-full-error', message || '');
                next.setAttribute('tabindex', '0');
            }
        }
    };

    var setSuccess = function (element) {
        if (!element) return;
        var inputControl = element.closest ? element.closest('.input-field') : element.parentElement;
        if (inputControl) {
            var errorDisplay = inputControl.querySelector(".error");
            if (errorDisplay) {
                errorDisplay.innerText = "";
                errorDisplay.removeAttribute('data-full-error');
                errorDisplay.removeAttribute('tabindex');
                try { errorDisplay.removeAttribute('data-bag'); } catch (e) {}
            }
            inputControl.classList.add("success");
            inputControl.classList.remove("error");
            try { element.removeAttribute('aria-invalid'); } catch (e) {}
            try { element.classList.remove('is-invalid'); } catch (e) {}
            try { element.classList.add('is-valid'); } catch (e) {}
        }
    };

    // ----------------------
    // Field validation
    // ----------------------
    var getFieldName = function (id) {
        return id ? id.replace(/^(register_|login_)/, '') : '';
    };

    var validateField = function (input) {
        if (!input) return;
        if (input.type && input.type.toLowerCase() === 'hidden') return;
        var value = input.value === undefined ? '' : input.value.trim();
        var name = getFieldName(input.id);
        var form = input.closest ? input.closest('form') : null;

        switch (name) {
            case "first_name":
                if (!value) setError(input, "First name is required");
                else if (!isValidName(value)) setError(input, "Only letters, spaces, and - are allowed");
                else setSuccess(input);
                break;

            case "middle_name":
                if (value && !isValidName(value)) setError(input, "Only letters, spaces, and - are allowed");
                else if (value) setSuccess(input);
                else {
                    var parent = input.closest ? input.closest('.input-field') : input.parentElement;
                    if (parent) parent.classList.remove('error', 'success');
                }
                break;

            case "last_name":
                if (!value) setError(input, "Last name is required");
                else if (!isValidName(value)) setError(input, "Only letters, spaces, and - are allowed");
                else setSuccess(input);
                break;

            case "email":
                if (!value) setError(input, "Email is required");
                else if (!isValidEmail(value)) setError(input, "Provide a valid email address");
                else setSuccess(input);
                break;

            case "phone":
                var rawPhone = (input.dataset && input.dataset.cleanValue) ? input.dataset.cleanValue : (input.value || '').toString().replace(/\D/g, '');
                if (!rawPhone) setError(input, "Phone number is required");
                else if (!isValidPhone(rawPhone)) setError(input, "Phone must be 7–11 digits (numbers only)");
                else setSuccess(input);
                break;

            case "address":
                !value ? setError(input, "Address is required") : setSuccess(input);
                break;

            case "password":
                if (!value) setError(input, "Password is required");
                else if (!isStrongPassword(value)) setError(input, "Min 8 chars with upper, lower, number & symbol");
                else setSuccess(input);
                if (form) {
                    var pwConfirm = form.querySelector('input[name="password_confirmation"]');
                    if (pwConfirm) validateField(pwConfirm);
                }
                break;

            case "password_confirmation":
                if (form) {
                    var pw = form.querySelector('input[name="password"]');
                    if (!value) setError(input, "Please confirm your password");
                    else if (pw && pw.value.trim() !== value) setError(input, "Passwords don't match");
                    else setSuccess(input);
                }
                break;

            default:
                if (!value) {
                    var parent2 = input.closest ? input.closest('.input-field') : input.parentElement;
                    if (parent2) parent2.classList.remove('error', 'success');
                } else {
                    setSuccess(input);
                }
        }
    };

    // ----------------------
    // Helper: is form visible
    // ----------------------
    var isFormVisible = function (form) {
        var panel = form.closest ? form.closest('.auth-panel') : null;
        if (!panel) return true;
        if (panel.hidden) return false;
        if (panel.hasAttribute && panel.hasAttribute('inert')) return false;
        return true;
    };

    // ----------------------
    // Hook forms: validation + phone formatting + reset handling
    // ----------------------
    var forms = document.querySelectorAll('.auth-form');
    for (var fi = 0; fi < forms.length; fi += 1) {
        (function () {
            var form = forms[fi];
            if (!form) return;
            var submitting = false;
            var submitButtons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'));

            // ensure reset clears UI state (covers programmatic form.reset())
            try {
                form.addEventListener('reset', function () {
                    try { clearFormUI(form); } catch (e) {}
                });
            } catch (e) {}

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!isFormVisible(form)) return;
                if (submitting) return;

                var inputs = Array.prototype.slice.call(form.querySelectorAll('input:not([type="hidden"])'));
                inputs.forEach(function (i) { validateField(i); });

                var errors = form.querySelectorAll('.input-field.error');

                if (errors.length === 0) {
                    // prepare phone value: set clean digits-only string into the input before submit
                    var phoneInput = form.querySelector('input[name="phone"]');
                    if (phoneInput) {
                        phoneInput.value = phoneInput.dataset.cleanValue || (phoneInput.value || '').toString().replace(/\D/g, '');
                    }

                    submitting = true;
                    submitButtons.forEach(function (b) { try { b.disabled = true; b.setAttribute('aria-disabled', 'true'); } catch (e) {} });
                    try { form.submit(); }
                    catch (err) {
                        submitButtons.forEach(function (b) { try { b.disabled = false; b.removeAttribute('aria-disabled'); } catch (e) {} });
                        submitting = false;
                    }
                } else {
                    var firstError = form.querySelector('.input-field.error input');
                    if (firstError) {
                        try { firstError.scrollIntoView({ block: 'center' }); } catch (ee) {}
                        try { firstError.focus({ preventScroll: true }); } catch (ee) { try { firstError.focus(); } catch (_) {} }
                    }
                    submitting = false;
                }
            });

            // per-input wiring
            var inputsAll = form.querySelectorAll('input:not([type="hidden"])');
            for (var ii = 0; ii < inputsAll.length; ii += 1) {
                (function () {
                    var input = inputsAll[ii];
                    if (!input) return;

                    // initial has-value state (server-provided old() values included)
                    try { if (input.value && input.value.trim() !== '') input.classList.add('has-value'); } catch (e) {}

                    // input listener
                    input.addEventListener('input', function () {
                        if (!isFormVisible(form)) return;

                        if (input.name === 'phone') {
                            var digits = (input.value || '').toString().replace(/\D/g, '').slice(0, 11);
                            input.dataset.cleanValue = digits;
                            input.value = formatPhoneForDisplay(digits);
                            if (digits) input.classList.add('has-value'); else input.classList.remove('has-value');
                        } else {
                            if (input.value && input.value.trim() !== '') input.classList.add('has-value'); else input.classList.remove('has-value');
                        }

                        validateField(input);
                    });
                }());
            }
        }());
    }

    // ----------------------
    // Password-eye toggle (press & hold)
    // ----------------------
    var eyes = document.querySelectorAll('.password-eye');
    for (var ei = 0; ei < eyes.length; ei += 1) {
        (function () {
            var eye = eyes[ei];
            var targetSelector = eye.dataset ? eye.dataset.target : null;
            var icon = eye.querySelector('i');
            if (!targetSelector) return;
            var target = document.querySelector(targetSelector);
            if (!target) return;

            var show = function () {
                target.type = 'text';
                if (icon) {
                    try { icon.classList.replace('fa-eye', 'fa-eye-slash'); } catch (e) {}
                }
            };
            var hide = function () {
                target.type = 'password';
                if (icon) {
                    try { icon.classList.replace('fa-eye-slash', 'fa-eye'); } catch (e) {}
                }
            };

            eye.addEventListener('mousedown', show);
            eye.addEventListener('mouseup', hide);
            eye.addEventListener('mouseleave', hide);
            eye.addEventListener('touchstart', function (e) { e.preventDefault(); show(); }, { passive: false });
            eye.addEventListener('touchend', hide);
        }());
    }
});
