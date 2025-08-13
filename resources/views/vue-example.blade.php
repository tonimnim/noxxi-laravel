<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vue 3 Example - Laravel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Vue 3 + Laravel Integration</h1>
        
        <!-- Method 1: Using #app for single Vue instance -->
        <div id="app" class="mb-8">
            <button-showcase></button-showcase>
        </div>
        
        <!-- Method 2: Using data-vue-component for multiple instances -->
        <div 
            data-vue-component="ExampleComponent" 
            data-props='{"title": "Vue Component with Props"}'
            class="mb-8"
        ></div>
        
        <!-- Regular Blade content -->
        <div class="p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-2">Regular Blade Content</h2>
            <p class="text-gray-600">
                This is regular Blade template content. Vue components can be mixed with Blade templates seamlessly.
            </p>
        </div>
    </div>
</body>
</html>