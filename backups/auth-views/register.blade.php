@extends('layouts.auth')

@section('content')
<div class="wrapper larger-form">
    <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
        @csrf
        <h2>SIGN UP</h2>

        <!-- Name Fields Row -->
        <div class="form-row form-row-3">
            <div class="input-field @error('first_name') error @enderror">
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required placeholder=" " pattern="[A-Za-z\s-]+" autofocus >
                <label for="first_name">First Name</label>
                <div class="error">
                    @error('first_name') {{ $message }} @enderror
                </div>
            </div>

            <div class="input-field @error('middle_name') error @enderror">
                <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder=" " pattern="[A-Za-z\s-]+">
                <label for="middle_name">Middle Name <span class="optional">(Optional)</span></label>
                <div class="error">
                    @error('middle_name') {{ $message }} @enderror
                </div>
            </div>

            <div class="input-field @error('last_name') error @enderror">
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required placeholder=" " pattern="[A-Za-z\s-]+">
                <label for="last_name">Last Name</label>
                <div class="error">
                    @error('last_name') {{ $message }} @enderror
                </div>
            </div>
        </div>

        <!-- Phone & Email Row -->
        <div class="form-row">
            <div class="input-field @error('phone') error @enderror">
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required placeholder=" ">
                <label for="phone">Phone</label>
                <div class="error">
                    @error('phone') {{ $message }} @enderror
                </div>
            </div>

            <div class="input-field @error('email') error @enderror">
                <input id="email" type="text" name="email" value="{{ old('email') }}" required placeholder=" ">
                <label for="email">Email</label>
                <div class="error">
                    @error('email') {{ $message }} @enderror
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="input-field @error('address') error @enderror">
            <input id="address" type="text" name="address" value="{{ old('address') }}" required placeholder=" ">
            <label for="address">Address</label>
            <div class="error">
                @error('address') {{ $message }} @enderror
            </div>
        </div>

        <!-- Password -->
        <div class="input-field @error('password') error @enderror">
            <input id="password" type="password" name="password" required placeholder=" ">
            <label for="password">Password</label>
            <!-- 👁️ Eye icon -->
    <span class="password-eye" data-target="#password">
        <i class="fa-solid fa-eye"></i>
    </span>
            <div class="error">
                @error('password') {{ $message }} @enderror
            </div>
        </div>

        <!-- Confirm Password --> 
        <div class="input-field @error('password_confirmation') error @enderror">
            <input id="password_confirmation" type="password" name="password_confirmation" required placeholder=" ">
            <label for="password_confirmation">Confirm Password</label>
           
            <!-- 👁️ Eye icon -->
    <span class="password-eye" data-target="#password_confirmation">
        <i class="fa-solid fa-eye"></i>
    </span>
            <div class="error">
                @error('password_confirmation') {{ $message }} @enderror
            </div>
        </div>

        <button type="submit">Register</button>

        <div class="register">
            <p>Already have an account? <a href="{{ route('login') }}">Login</a></p>
        </div>
    </form>
</div>

<script src="{{ asset('js/validation.js') }}"></script>

@endsection
