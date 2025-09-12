<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scanner</title>
    @vite(['resources/css/app.css', 'resources/js/scanner-app.js'])
</head>
<body>
    <div id="scanner-app">
        <mobile-scanner></mobile-scanner>
    </div>
</body>
</html>