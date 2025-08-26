<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NOXXI') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
    @livewireStyles
    
    <!-- Favicon -->
    @include('partials.favicon')
    
    <!-- Auth Check -->
    @include('partials.auth-check')
</head>
<body>
    <flux:main>
        {{ $slot }}
    </flux:main>
    @fluxScripts
    @livewireScripts
</body>
</html>