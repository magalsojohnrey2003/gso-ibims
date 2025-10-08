<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logo2.png') }}">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Auth-specific CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>
