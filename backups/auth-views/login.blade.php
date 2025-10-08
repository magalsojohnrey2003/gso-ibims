@extends('layouts.auth')

@section('content')
<div class="wrapper login-form @if($errors->any()) shake @endif" id="authWrapper">
    <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
        @csrf
        
        <h2>LOGIN </h2>

        {{-- Show error alert --}}
        @if ($errors->any())
            <div class="error-alert">
                These credentials do not match our records.
            </div>
        @endif

        <!-- Email -->
        <div class="input-field @error('email') error @enderror">
            <input id="email" type="text" name="email" value="{{ old('email') }}" placeholder=" " required autofocus>
            <label for="email">Email</label>
            <div class="error">
                @error('email') {{ $message }} @enderror
            </div>
        </div>

        <!-- Password -->
        <div class="input-field @error('password') error @enderror">
            <input id="password" type="password" name="password" placeholder=" " required>
            <label for="password">Password</label>
            <!-- 👁️ Eye icon -->
                <span class="password-eye" data-target="#password">
        <i class="fa-solid fa-eye"></i>
    </span>
            <div class="error">
                @error('password') {{ $message }} @enderror
            </div>
        </div>

        <!-- Remember + Forgot password -->
        <div class="form-options">
            <label class="remember">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
            @endif
        </div>

        <button type="submit">Log In</button>

        <div class="register">
            <p>Don't have an account? <a href="{{ route('register') }}">Register</a></p>
        </div>
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const wrapper = document.getElementById("authWrapper");
        if (wrapper && wrapper.classList.contains("shake")) {
            wrapper.addEventListener("animationend", () => {
                wrapper.classList.remove("shake");
            });
        }
    });
</script>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection
