<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Account - {{ config('app.name', 'NOXXI') }}</title>
    
    @include('partials.favicon')
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script>
        window.__INITIAL_DATA__ = @json($initialData);
    </script>
</head>
<body>
    <div id="app" data-initial="{{ json_encode($initialData) }}">
        <!-- Header -->
        <app-header></app-header>
        
        <!-- Account Component -->
        <user-account></user-account>
        
        <!-- Footer -->
        <app-footer></app-footer>
    </div>
</body>
</html>