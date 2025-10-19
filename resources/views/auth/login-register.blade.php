@extends('layouts.auth')

@section('content')
@php
    $initial = 'login'; // ✅ Default to login

    if (old('auth_form') === 'login') {
        $initial = 'login';
    } elseif (old('auth_form') === 'register') {
        $initial = 'register';
    } elseif ($errors->getBag('login')->any()) {
        $initial = 'login';
    } elseif ($errors->getBag('register')->any()) {
        $initial = 'register';
    } else {
        if (old('first_name') || old('password_confirmation')) {
            $initial = 'register';
        } elseif (old('email') && !old('first_name')) {
            $initial = 'login';
        }
    }
@endphp

<div class="auth-shell">
    <!-- Wrapper: responsive max width so mobile/tablet users get a usable full-width experience -->
    <div class="wrapper larger-form auth-wrapper {{ $initial === 'login' && ($errors->getBag('login')->any() || $errors->getBag('register')->any()) ? 'shake' : '' }}"
         id="authWrapper" data-initial="{{ $initial }}" aria-live="polite">

        <!-- Slider: behaves as a two-column slider on desktop, stacks panels on small screens -->
        <div class="auth-slider" id="authSlider" role="region" aria-label="Authentication forms">

            <!-- REGISTER -->
            <div class="auth-panel register-panel" @if($initial === 'login') hidden inert @endif>
                <form method="POST" action="{{ route('register') }}" id="registerForm" class="auth-form" novalidate>
                    @csrf
                    <input type="hidden" name="auth_form" value="register">

                    <h2>SIGN UP</h2>

                    <div class="form-row form-row-3">
                        @php $err = $errors->getBag('register')->first('first_name'); @endphp
                        <div class="input-field {{ $err ? 'error' : '' }}">
                            <input id="register_first_name" type="text" name="first_name" value="{{ old('auth_form') === 'register' ? old('first_name') : '' }}" required placeholder=" " pattern="[A-Za-z\s-]+">
                            <label for="register_first_name">First Name</label>
                            @if($err)
                                <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                            @else
                                <div class="error" aria-hidden="true"></div>
                            @endif
                        </div>

                        @php $err = $errors->getBag('register')->first('middle_name'); @endphp
                        <div class="input-field {{ $err ? 'error' : '' }}">
                            <input id="register_middle_name" type="text" name="middle_name" value="{{ old('auth_form') === 'register' ? old('middle_name') : '' }}" placeholder=" " pattern="[A-Za-z\s-]+">
                            <label for="register_middle_name">Middle Name</label>
                            @if($err)
                                <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                            @else
                                <div class="error" aria-hidden="true"></div>
                            @endif
                        </div>

                        @php $err = $errors->getBag('register')->first('last_name'); @endphp
                        <div class="input-field {{ $err ? 'error' : '' }}">
                            <input id="register_last_name" type="text" name="last_name" value="{{ old('auth_form') === 'register' ? old('last_name') : '' }}" required placeholder=" " pattern="[A-Za-z\s-]+">
                            <label for="register_last_name">Last Name</label>
                            @if($err)
                                <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                            @else
                                <div class="error" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        @php $err = $errors->getBag('register')->first('phone'); @endphp
                        <div class="input-field {{ $err ? 'error' : '' }}">
                            <input id="register_phone" type="tel" name="phone" value="{{ old('auth_form') === 'register' ? old('phone') : '' }}" placeholder=" "
                                   inputmode="numeric" maxlength="14" autocomplete="tel">
                            <label for="register_phone">Phone</label>
                            @if($err)
                                <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                            @else
                                <div class="error" aria-hidden="true"></div>
                            @endif
                        </div>

                        @php $err = $errors->getBag('register')->first('email'); @endphp
                        <div class="input-field {{ $err ? 'error' : '' }}">
                            <input id="register_email" type="text" name="email" value="{{ old('auth_form') === 'register' ? old('email') : '' }}" placeholder=" ">
                            <label for="register_email">Email</label>
                            @if($err)
                                <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                            @else
                                <div class="error" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>

                    @php $err = $errors->getBag('register')->first('address'); @endphp
                    <div class="input-field {{ $err ? 'error' : '' }}">
                        <input id="register_address" type="text" name="address" value="{{ old('auth_form') === 'register' ? old('address') : '' }}" placeholder=" ">
                        <label for="register_address">Address</label>
                        @if($err)
                            <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                        @else
                            <div class="error" aria-hidden="true"></div>
                        @endif
                    </div>

                    @php $err = $errors->getBag('register')->first('password'); @endphp
                    <div class="input-field {{ $err ? 'error' : '' }}">
                        <input id="register_password" type="password" name="password" placeholder=" ">
                        <label for="register_password">Password</label>
                        <span class="password-eye" data-target="#register_password"><i class="fa-solid fa-eye"></i></span>
                        @if($err)
                            <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                        @else
                            <div class="error" aria-hidden="true"></div>
                        @endif
                    </div>

                    @php $err = $errors->getBag('register')->first('password_confirmation'); @endphp
                    <div class="input-field {{ $err ? 'error' : '' }}">
                        <input id="register_password_confirmation" type="password" name="password_confirmation" placeholder=" ">
                        <label for="register_password_confirmation">Confirm Password</label>
                        <span class="password-eye" data-target="#register_password_confirmation"><i class="fa-solid fa-eye"></i></span>
                        @if($err)
                            <div class="error" data-bag="register" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                        @else
                            <div class="error" aria-hidden="true"></div>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary w-full mt-3">Register</button>

                    <div class="register mt-4 text-center">
                        <p>Already have an account? <a href="#" class="switch-text" data-target="login">Login</a></p>
                    </div>
                </form>
            </div>

            <!-- LOGIN -->
            <div class="auth-panel login-panel" @if($initial === 'register') hidden inert @endif>
                <form method="POST" action="{{ route('login') }}" id="loginForm" class="auth-form" novalidate>
                    @csrf
                    <input type="hidden" name="auth_form" value="login">

                    <h2>LOGIN</h2>

                    @php $err = $errors->getBag('login')->first('email'); @endphp
                    <div class="input-field {{ $err ? 'error' : '' }}">
                        <input id="login_email" type="text" name="email" value="{{ old('auth_form') === 'login' ? old('email') : '' }}" placeholder=" " required>
                        <label for="login_email">Email</label>
                        @if($err)
                            <div class="error" data-bag="login" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                        @else
                            <div class="error" aria-hidden="true"></div>
                        @endif
                    </div>

                    @php $err = $errors->getBag('login')->first('password'); @endphp
                    <div class="input-field {{ $err ? 'error' : '' }}">
                        <input id="login_password" type="password" name="password" placeholder=" " required>
                        <label for="login_password">Password</label>
                        <span class="password-eye" data-target="#login_password"><i class="fa-solid fa-eye"></i></span>
                        @if($err)
                            <div class="error" data-bag="login" data-full-error="{{ $err }}" tabindex="0">{{ $err }}</div>
                        @else
                            <div class="error" aria-hidden="true"></div>
                        @endif
                    </div>

                    <div class="form-options flex items-center justify-between mt-2">
                        <label class="remember inline-flex items-center">
                            <input id="login_remember" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span class="ml-2">Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link text-sm">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary w-full mt-4">Log In</button>

                    <div class="register mt-4 text-center">
                        <p>Don't have an account? <a href="#" class="switch-text" data-target="register">Register</a></p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cover -->
        <div class="auth-cover" id="authCover" aria-hidden="true">
          <div class="cover-content">
            {{-- register success banner inside cover (slides in then out) --}}
            @if(session('status') === 'register-success')
            <div id="coverRegisterBanner" class="cover-register-banner" role="status" aria-live="polite">
                <i class="fa-solid fa-circle-check" aria-hidden="true" style="font-size:16px"></i>
                <span>{{ session('login_message') ?? 'Registration successful!' }}</span>
            </div>
            @endif

            <div class="cover-content-inner">
              <img src="{{ asset('images/logo2.png') }}" alt="Tagoloan Municipal Government — General Services Office logo" class="cover-logo">
              <div class="cover-text">
                <strong class="cover-title">GSO Item Borrowing & Inventory System</strong><br>
                <span class="cover-sub">Tagoloan Municipal Government — General Services Office</span><br><br>
                <p class="cover-desc">
                  This secure platform automates item requests, approvals, and inventory tracking for authorized personnel.
                </p>
                <br><br><br>
                <small class="cover-meta">Version 2.0 • May 2025</small>
              </div>
            </div>
          </div>
        </div>

    </div>
</div>

<!-- EARLY guard: position the cover quickly to avoid flash -->
<script>
(function () {
    var wrapperEl = document.getElementById('authWrapper');
    var serverInitial = 'register';
    if (wrapperEl && wrapperEl.getAttribute) {
        var di = wrapperEl.getAttribute('data-initial');
        if (di) serverInitial = di;
    }

    var cover = document.getElementById('authCover');
    var registerPanel = document.querySelector('.register-panel');
    var loginPanel = document.querySelector('.login-panel');

    if (!cover) return;

    // Keep cover placement simple and responsive:
    if (serverInitial === 'register') {
        if (registerPanel) { registerPanel.hidden = false; registerPanel.removeAttribute('inert'); }
        if (loginPanel) { loginPanel.hidden = true; loginPanel.setAttribute('inert', ''); }
        cover.classList.add('cover-right');
        cover.classList.remove('cover-left');
    } else {
        if (registerPanel) { registerPanel.hidden = true; registerPanel.setAttribute('inert', ''); }
        if (loginPanel) { loginPanel.hidden = false; loginPanel.removeAttribute('inert'); }
        cover.classList.add('cover-left');
        cover.classList.remove('cover-right');
    }

    try {
        var aw = document.getElementById('authWrapper');
        if (aw && typeof aw.scrollIntoView === 'function') {
            aw.scrollIntoView({behavior:'auto', block:'center'});
        }
    } catch(e) {}
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cover = document.getElementById('authCover');
    var wrapper = document.getElementById('authWrapper');
    var switchLinks = Array.prototype.slice.call(document.querySelectorAll('.switch-text'));
    var registerPanel = document.querySelector('.register-panel');
    var loginPanel = document.querySelector('.login-panel');
    var registerForm = document.getElementById('registerForm');
    var loginForm = document.getElementById('loginForm');

    function showPanel(target) {
        var toShow = (target === 'register') ? registerPanel : loginPanel;
        var toHide = (target === 'register') ? loginPanel : registerPanel;

        if (toShow) { toShow.hidden = false; if (toShow.removeAttribute) toShow.removeAttribute('inert'); }
        if (toHide) { toHide.hidden = true; if (toHide.setAttribute) toHide.setAttribute('inert',''); }

        if (cover) {
            if (target === 'register') {
                cover.classList.remove('cover-left');
                cover.classList.add('cover-right');
            } else {
                cover.classList.remove('cover-right');
                cover.classList.add('cover-left');
            }
        }

        try { wrapper && wrapper.scrollIntoView && wrapper.scrollIntoView({behavior:'auto', block:'center'}); } catch (_) {}
    }

    // hook footer links — clear opposite form & UI state
    switchLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var target = this.dataset.target;

            var oppForm = (target === 'login') ? registerForm : loginForm;
            if (oppForm) {
                try { oppForm.reset(); } catch(e) {}
                if (window.gsoClearFormUI && typeof window.gsoClearFormUI === 'function') {
                    try { window.gsoClearFormUI(oppForm); } catch(e) {}
                }
            }

            showPanel(target);

            setTimeout(function () {
                var focusEl = document.querySelector(target === 'register' ? '#register_first_name' : '#login_email');
                if (focusEl && typeof focusEl.focus === 'function') { try { focusEl.focus({ preventScroll: true }); } catch (e) { try { focusEl.focus(); } catch (_) {} } }
            }, 420);
        });
    });

    // If server returned validation errors, reveal panel and scroll the first error into view
    var firstErrorInput = document.querySelector('.input-field.error input');
    if (firstErrorInput) {
        var formEl = firstErrorInput.closest ? firstErrorInput.closest('form') : null;
        var isRegister = formEl && formEl.id === 'registerForm';
        var target = isRegister ? 'register' : 'login';
        showPanel(target);

        setTimeout(function () {
            try {
                if (formEl) {
                    var offset = firstErrorInput.offsetTop || 0;
                    formEl.scrollTop = Math.max(0, offset - 12);
                }
                if (typeof firstErrorInput.focus === 'function') {
                    try { firstErrorInput.focus({ preventScroll: true }); } catch (e) { firstErrorInput.focus(); }
                }
                try { wrapper && wrapper.scrollIntoView && wrapper.scrollIntoView({behavior:'auto', block:'center'}); } catch (e) {}
            } catch (e) { try { firstErrorInput.focus(); } catch(_) {} }
        }, 60);
    } else {
        // normal init focus
        showPanel((wrapper && wrapper.getAttribute && wrapper.getAttribute('data-initial')) || 'register');
        setTimeout(function () {
            var el = (wrapper && wrapper.getAttribute && wrapper.getAttribute('data-initial') === 'register') ? document.querySelector('#register_first_name') : document.querySelector('#login_email');
            if (el && typeof el.focus === 'function') { try { el.focus(); } catch (_) {} }
        }, 420);
    }

    // Cover register banner auto show/hide + auto-switch to login
    (function () {
        var coverBanner = document.getElementById('coverRegisterBanner');
        if (!coverBanner) return;

        // slide in
        requestAnimationFrame(function () {
            coverBanner.classList.add('visible');
        });

        // auto-switch after ~2.2s, then hide the banner shortly after
        var autoSwitchTimeout = setTimeout(function () {
            try {
                if (registerForm) {
                    try { registerForm.reset(); } catch (e) {}
                    if (window.gsoClearFormUI && typeof window.gsoClearFormUI === 'function') {
                        try { window.gsoClearFormUI(registerForm); } catch (e) {}
                    }
                }
                try { showPanel('login'); } catch (e) {}
                try {
                    var loginEl = document.querySelector('#login_email');
                    if (loginEl && typeof loginEl.focus === 'function') {
                        setTimeout(function () { try { loginEl.focus({ preventScroll: true }); } catch (e) { try { loginEl.focus(); } catch (_) {} } }, 260);
                    }
                } catch (e) {}
            } catch (e) {}
        }, 2200);

        var hideTimeout = setTimeout(function () {
            coverBanner.classList.remove('visible');
            setTimeout(function () { try { coverBanner.remove(); } catch (e) {} }, 320);
        }, 2600);

        var closeBtn = coverBanner.querySelector('.cover-banner-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                clearTimeout(hideTimeout);
                clearTimeout(autoSwitchTimeout);
                coverBanner.classList.remove('visible');
                setTimeout(function () { try { coverBanner.remove(); } catch (e) {} }, 220);
            });
        }
    }());

    // remove shake when animation ends
    if (wrapper && wrapper.addEventListener) {
        wrapper.addEventListener('animationend', function () { wrapper.classList.remove('shake'); });
    }
});
</script>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection