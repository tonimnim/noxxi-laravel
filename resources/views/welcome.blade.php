<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Noxxi - Your Gateway to Amazing Events Across Africa</title>
    
    @include('partials.favicon')
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Preload custom font to prevent FOUC -->
    <link rel="preload" href="/fonts/SilkRemington-SBold.ttf" as="font" type="font/ttf" crossorigin>
    
    <!-- Critical CSS for font loading -->
    <style>
        @font-face {
            font-family: 'SilkRemington';
            src: url('/fonts/SilkRemington-SBold.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
            font-display: swap; /* Use swap for better UX */
        }
        
        .font-silk {
            font-family: 'SilkRemington', Georgia, serif;
            font-weight: bold;
            /* Adjust metrics to match SilkRemington to minimize shift */
            font-size-adjust: 0.5;
            letter-spacing: -0.02em;
        }
    </style>
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>
<body class="antialiased">
    <div id="app">
        <app></app>
    </div>
</body>
</html>