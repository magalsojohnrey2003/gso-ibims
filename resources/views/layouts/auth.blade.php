<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo2.png') }}">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <!-- Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Auth-specific CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <!-- Use main app.css too to get theme variables & utility classes available on auth pages -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Responsive overrides dedicated for mobile scaling -->
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>