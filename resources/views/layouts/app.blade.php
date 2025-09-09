<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'NOXXI') }} - Event Ticketing Platform</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <!-- Favicon -->
    @include('partials.favicon')
    
    <!-- Auth Check -->
    @include('partials.auth-check')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="text-2xl font-bold text-indigo-600">NOXXI</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="#" class="text-gray-700 hover:text-indigo-600 transition">Events</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600 transition">About</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600 transition">Contact</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-2xl font-bold mb-4">NOXXI</h3>
                        <p class="text-gray-400">Your premier event ticketing platform in Kenya.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white transition">Events</a></li>
                            <li><a href="#" class="hover:text-white transition">Organizers</a></li>
                            <li><a href="#" class="hover:text-white transition">Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">For Organizers</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/organizer/register" class="hover:text-white transition">Sell with Us</a></li>
                            <li><a href="#" class="hover:text-white transition">Pricing</a></li>
                            <li><a href="#" class="hover:text-white transition">Resources</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Download App</h4>
                        <p class="text-gray-400 mb-4">Get the best experience on mobile</p>
                        <div class="space-y-2">
                            <button class="bg-gray-800 px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                                App Store
                            </button>
                            <button class="bg-gray-800 px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                                Google Play
                            </button>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                    <p>&copy; {{ date('Y') }} NOXXI. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>