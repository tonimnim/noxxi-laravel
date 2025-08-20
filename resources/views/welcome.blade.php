<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Noxxi - Your Gateway to Amazing Events Across Africa</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Briski Font */
        @font-face {
            font-family: 'Briski';
            src: url('/BiskiTrial-Regular.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        
        .logo-briski {
            font-family: 'Briski', serif;
        }
    </style>
</head>
<body class="antialiased">
    <div id="app">
        <app></app>
    </div>
</body>
</html>