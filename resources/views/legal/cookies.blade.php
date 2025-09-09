<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Cookie Policy - Noxxi</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css'])
    
    <!-- Favicon -->
    @include('partials.favicon')
    
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
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-50">
        <!-- Static Header -->
        @include('partials.static-header')
        
        <!-- Page Content with top margin to account for fixed header -->
        <main class="pt-20 md:pt-24">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Noxxi Cookie Policy</h1>
            <p class="text-gray-600">Last Updated: 30th September 2025</p>
        </div>

        <!-- Policy Introduction -->
        <div class="mb-8 p-4 bg-blue-50 border-l-4 border-blue-400">
            <p class="text-gray-700">
                This Cookie Policy explains how Noxxi ("we," "our," or "us") uses cookies and similar tracking technologies on our website and platforms. By using our services, you agree to the use of cookies as described in this policy.
            </p>
        </div>

        <!-- Content -->
        <div class="prose max-w-none">
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. What Are Cookies?</h2>
                <p class="text-gray-700 mb-4">
                    Cookies are small text files that are placed on your device (computer, tablet, or mobile) when you visit a website. They help websites function properly, improve user experience, and provide analytical insights.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Types of Cookies We Use</h2>
                
                <div class="space-y-6">
                    <div class="border-l-4 border-green-400 bg-green-50 p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Essential Cookies</h3>
                        <ul class="text-gray-700 list-disc pl-4 space-y-1">
                            <li>Necessary for the website to function (e.g., login, security, checkout).</li>
                            <li>Cannot be disabled in our systems.</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-blue-400 bg-blue-50 p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance & Analytics Cookies</h3>
                        <ul class="text-gray-700 list-disc pl-4 space-y-1">
                            <li>Help us understand how visitors interact with our site (e.g., pages visited, time spent).</li>
                            <li>We use tools like Google Analytics or equivalent services.</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-purple-400 bg-purple-50 p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Functional Cookies</h3>
                        <ul class="text-gray-700 list-disc pl-4 space-y-1">
                            <li>Remember user preferences (e.g., language, location, saved settings).</li>
                            <li>Enhance personalized experiences.</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-orange-400 bg-orange-50 p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Advertising & Targeting Cookies</h3>
                        <ul class="text-gray-700 list-disc pl-4 space-y-1">
                            <li>Used to deliver relevant ads based on browsing behavior.</li>
                            <li>May be set by us or by third-party partners (e.g., social media platforms, ad networks).</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. How We Use Cookies</h2>
                <ul class="text-gray-700 list-disc pl-6 space-y-2">
                    <li>To ensure the website operates smoothly.</li>
                    <li>To personalize content and recommendations.</li>
                    <li>To analyze traffic and measure performance.</li>
                    <li>To deliver targeted marketing and advertising.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Third-Party Cookies</h2>
                <p class="text-gray-700 mb-4">
                    Some cookies are placed by third parties on our site (e.g., analytics providers, payment gateways, advertising networks). These third parties may use cookies to collect information about your browsing activities across websites.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Managing & Disabling Cookies</h2>
                <p class="text-gray-700 mb-4">
                    You can control cookies through your browser settings. Most browsers allow you to:
                </p>
                <ul class="text-gray-700 list-disc pl-6 space-y-2 mb-4">
                    <li>Block all cookies.</li>
                    <li>Delete existing cookies.</li>
                    <li>Receive alerts before cookies are stored.</li>
                </ul>
                
                <div class="p-4 bg-amber-50 border-l-4 border-amber-400 mb-4">
                    <p class="text-gray-700">
                        <strong>Please note:</strong> Disabling certain cookies may impact site functionality and user experience.
                    </p>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-3">For detailed instructions, visit your browser's help pages:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <a href="https://support.google.com/chrome/answer/95647" 
                           target="_blank" 
                           class="flex items-center p-3 bg-gray-50 border rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-red-500 rounded mr-3 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 font-medium">Google Chrome</span>
                        </a>
                        
                        <a href="https://support.mozilla.org/en-US/kb/enable-and-disable-cookies-website-preferences" 
                           target="_blank" 
                           class="flex items-center p-3 bg-gray-50 border rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-orange-500 rounded mr-3 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 font-medium">Mozilla Firefox</span>
                        </a>
                        
                        <a href="https://support.apple.com/en-us/HT201265" 
                           target="_blank" 
                           class="flex items-center p-3 bg-gray-50 border rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-blue-500 rounded mr-3 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 font-medium">Safari</span>
                        </a>
                        
                        <a href="https://support.microsoft.com/en-us/windows/delete-and-manage-cookies-168dab11-0753-043d-7c16-ede5947fc64d" 
                           target="_blank" 
                           class="flex items-center p-3 bg-gray-50 border rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-indigo-500 rounded mr-3 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 font-medium">Microsoft Edge</span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Legal Basis for Processing (GDPR/Global Compliance)</h2>
                <p class="text-gray-700 mb-4">Where applicable, we rely on:</p>
                
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Consent</h3>
                            <p class="text-gray-700">For non-essential cookies (e.g., analytics, advertising).</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Legitimate Interest</h3>
                            <p class="text-gray-700">For essential cookies needed to operate the platform.</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 bg-green-50 border-l-4 border-green-400 mt-4">
                    <p class="text-gray-700">
                        <strong>Your Rights:</strong> Users in regions covered by GDPR, CCPA, or similar laws have the right to manage cookie preferences and opt out of non-essential cookies.
                    </p>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Updates to This Policy</h2>
                <p class="text-gray-700 mb-4">
                    We may update this Cookie Policy from time to time to reflect changes in technology, legal requirements, or our practices. Updates will be posted on this page with a new "Last Updated" date.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Contact Us</h2>
                <p class="text-gray-700 mb-4">
                    If you have any questions about this Cookie Policy or our use of cookies, contact us at:
                </p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center mb-2">
                        <span class="text-xl mr-2">📧</span>
                        <p class="text-gray-700">
                            <strong>Email:</strong> 
                            <a href="mailto:info@noxxi.com" class="text-indigo-600 hover:text-indigo-800">info@noxxi.com</a>
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xl mr-2">📍</span>
                        <p class="text-gray-700">
                            <strong>Address:</strong> Kerugoya, Kirinyaga County (HQ)
                        </p>
                    </div>
                </div>
            </section>
        </div>

        </div>
    </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white mt-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
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

</body>
</html>