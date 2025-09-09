<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Refund Policy - Noxxi</title>

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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Noxxi Event Postponement and Cancellation Refund Policy</h1>
            <p class="text-gray-600">Last Updated: 30th September 2025</p>
        </div>

        <!-- Policy Introduction -->
        <div class="mb-8 p-4 bg-amber-50 border-l-4 border-amber-400">
            <p class="text-gray-700">
                This policy governs ticket refunds for events organized in collaboration with Noxxi. By purchasing a ticket, the buyer acknowledges and agrees to these terms and conditions. These terms are legally binding and constitute the entire agreement between the ticket holder and Noxxi.
            </p>
        </div>

        <!-- Content -->
        <div class="prose max-w-none">
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Event Postponement</h2>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        <strong>All tickets purchased for a postponed event will remain valid for the rescheduled date.</strong>
                    </p>
                    
                    <p class="text-gray-700">
                        Refunds for postponed events may only be requested within <strong>seven (7) working days</strong> from the official postponement announcement. Requests made after this period will not be honored.
                    </p>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Refunds for postponed events are subject to:</h3>
                        <ul class="text-gray-700 list-disc pl-6 space-y-2">
                            <li>Consultation and approval by the event organizer and Noxxi.</li>
                            <li>Deduction of applicable transaction and processing fees.</li>
                            <li>Processing timelines of up to 30 working days, depending on the payment method and third-party payment processors. Noxxi is not responsible for delays caused by banks, mobile money operators, or payment gateways.</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Noxxi will make reasonable efforts to notify ticket holders of postponements via:</h3>
                        <ul class="text-gray-700 list-disc pl-6 space-y-2">
                            <li>The email address and mobile number provided at the time of purchase (using email, SMS, or chat applications).</li>
                            <li>Official announcements on Noxxi's digital channels.</li>
                        </ul>
                    </div>
                    
                    <div class="p-4 bg-red-50 border-l-4 border-red-400">
                        <p class="text-gray-700">
                            <strong>Important:</strong> Noxxi is not liable for any indirect or incidental losses resulting from event postponements, including but not limited to travel expenses, accommodation costs, or other related expenses.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Event Cancellation</h2>
                
                <div class="space-y-4">
                    <p class="text-gray-700">
                        In the event of cancellation, refunds will be processed only after consultation with the event organizer.
                    </p>
                    
                    <p class="text-gray-700">
                        If the organizer has already utilized ticket proceeds for event preparations, refunds will only be issued once the organizer replenishes the refund account. Ticket holders will be informed of the refund timeline in such cases.
                    </p>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Refunds will strictly be made:</h3>
                        <ul class="text-gray-700 list-disc pl-6 space-y-2">
                            <li>To the original payment method used during purchase.</li>
                            <li>For group purchases, refunds will only be issued to the original purchaser's account (e.g., the card or mobile money number used).</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Noxxi will make reasonable efforts to notify ticket holders of cancellations via:</h3>
                        <ul class="text-gray-700 list-disc pl-6 space-y-2">
                            <li>The email address and mobile number provided at purchase (using email or SMS).</li>
                            <li>Official announcements on Noxxi's digital channels.</li>
                        </ul>
                    </div>
                    
                    <div class="p-4 bg-blue-50 border-l-4 border-blue-400">
                        <p class="text-gray-700">
                            <strong>Time Limit:</strong> Refund requests must be submitted within seven (7) working days of the official cancellation announcement. Requests beyond this period will not be processed.
                        </p>
                    </div>
                    
                    <div class="p-4 bg-red-50 border-l-4 border-red-400">
                        <p class="text-gray-700">
                            <strong>Important:</strong> Noxxi shall not be held liable for refund delays or failures caused by the event organizer's inability to provide the necessary funds.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. General Terms</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Force Majeure</h3>
                        <p class="text-gray-700">
                            Noxxi shall not be liable for cancellations, postponements, or losses caused by events outside its reasonable control, including but not limited to natural disasters, government restrictions, public health emergencies, strikes, or acts of terrorism.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Transaction Fees</h3>
                        <p class="text-gray-700">
                            All refunds are subject to applicable processing fees, which will be deducted from the refunded amount. Ticket holders waive any right to dispute these deductions.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Refund Timelines</h3>
                        <p class="text-gray-700">
                            Refunds may take up to <strong>30 working days</strong>, depending on payment channels and third-party processors. Noxxi is not responsible for delays caused by financial institutions or mobile money operators.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Non-Transferability</h3>
                        <p class="text-gray-700">
                            Refunds will only be issued to the original payment method used at purchase. Tickets purchased through resale or transfer are not eligible for refunds unless expressly approved by both Noxxi and the event organizer.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Ticket Holder Obligations</h3>
                        <p class="text-gray-700">
                            Ticket holders are responsible for providing accurate and up-to-date contact details at the time of purchase. Noxxi is not liable for failure to deliver notices due to incorrect or outdated contact information.
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Jurisdiction & Governing Law</h3>
                        <p class="text-gray-700">
                            This policy shall be governed by the laws of the jurisdiction in which the transaction occurs. Any disputes will fall under the exclusive jurisdiction of the competent courts in that region.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Communication & Notification</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Noxxi will endeavor to communicate postponements or cancellations through:</h3>
                        <ul class="text-gray-700 list-disc pl-6 space-y-2">
                            <li>Email notifications sent to the registered email address.</li>
                            <li>SMS or messaging apps sent to the registered mobile number.</li>
                            <li>Official announcements published on Noxxi's digital channels.</li>
                        </ul>
                    </div>
                    
                    <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400">
                        <p class="text-gray-700">
                            <strong>Disclaimer:</strong> Noxxi shall not be liable if a ticket holder fails to receive such communications due to factors beyond its control, including but not limited to incorrect contact details, network issues, or spam filters.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Final Agreement -->
            <section class="mb-8">
                <div class="p-6 bg-gray-50 border border-gray-200 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Agreement & Waiver</h3>
                    <p class="text-gray-700">
                        By purchasing a ticket, the ticket holder acknowledges and agrees to this Refund Policy in full. The ticket holder waives any right to initiate claims or legal action against Noxxi in connection with postponement or cancellation, except as expressly provided for under this policy.
                    </p>
                </div>
            </section>

            <!-- Contact Information -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Us</h2>
                <p class="text-gray-700 mb-4">For refund requests or policy inquiries, please contact:</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700 mb-2">
                        <strong>Email:</strong> 
                        <a href="mailto:info@noxxi.com" class="text-indigo-600 hover:text-indigo-800">info@noxxi.com</a>
                    </p>
                    <p class="text-gray-700">
                        <strong>Address:</strong> Kollint Ventures Ltd., Kerugoya, Kirinyaga County, Kenya
                    </p>
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