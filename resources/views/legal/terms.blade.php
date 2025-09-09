<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Terms and Conditions of Use - Noxxi</title>

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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Noxxi Terms and Conditions of Use</h1>
            <p class="text-gray-600">Last Updated: 30th September 2025</p>
        </div>

        <!-- Content -->
        <div class="prose max-w-none">
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                <p class="text-gray-700 mb-4">
                    Welcome to the Noxxi Events/Travel website ("Noxxi"), managed by Kollint Ventures, a Kenyan company. 
                    By using our site (noxxi.com), you agree to these Terms and our Privacy Policy.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Acceptance of Terms</h2>
                <p class="text-gray-700 mb-4">
                    By accessing Noxxi, you confirm you have read, understood, and agreed to these Terms. 
                    If you disagree, please stop using the site.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Communications</h2>
                <p class="text-gray-700 mb-4">
                    By using Noxxi, you consent to receive newsletters, promotions, and updates. 
                    You may unsubscribe anytime.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Use of the Site</h2>
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Use Noxxi only for lawful purposes.</li>
                    <li>Do not damage, disable, or interfere with the site.</li>
                    <li>Noxxi may suspend or terminate accounts for violations.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Event Listings</h2>
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Only legitimate event organizers with proper rights/permits may list events.</li>
                    <li>You are responsible for the legality and accuracy of your content.</li>
                    <li>Noxxi may review, approve, reject, or remove listings.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Promotions & Discounts</h2>
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Must comply with Noxxi's rules and the rules of your areas of jurisdiction.</li>
                    <li>Violations may lead to removal or suspension and further escalation may lead to authority intervention.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Ticket Purchases</h2>
                
                <h3 class="text-lg font-medium text-gray-900 mb-3">Availability</h3>
                <p class="text-gray-700 mb-4">
                    All tickets are offered subject to availability and cannot be guaranteed until a confirmed purchase is completed.
                </p>

                <h3 class="text-lg font-medium text-gray-900 mb-3">Payments</h3>
                <p class="text-gray-700 mb-4">
                    Customers are required to provide accurate payment details at the time of purchase. Payments are processed through secure channels, and Noxxi reserves the right to decline or cancel any transaction suspected to be erroneous, incomplete, or fraudulent.
                </p>

                <h3 class="text-lg font-medium text-gray-900 mb-3">Delivery</h3>
                <p class="text-gray-700 mb-4">
                    Upon successful payment, tickets will be delivered digitally through the Noxxi mobile application and accessible via the customer's registered account.
                </p>

                <h3 class="text-lg font-medium text-gray-900 mb-3">Refunds & Cancellations</h3>
                <p class="text-gray-700 mb-4">
                    Refunds and cancellations are governed by the individual policies set by event organizers listing on the Noxxi platform. Customers are strongly advised to review the applicable policy before making a booking or purchasing a ticket.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Refund Policy for Postponement & Cancellation</h2>
                
                <h3 class="text-lg font-medium text-gray-900 mb-3">Event Postponement</h3>
                <p class="text-gray-700 mb-4">
                    Tickets purchased for a postponed event remain valid for the rescheduled date. Customers may request a refund within seven (7) working days of the postponement announcement. All approved refunds will be subject to applicable fees and may take up to 30 working days to process.
                </p>

                <h3 class="text-lg font-medium text-gray-900 mb-3">Event Cancellation</h3>
                <p class="text-gray-700 mb-4">
                    Refunds for canceled events are contingent on the event organizer reimbursing the ticketing account. Once funds are received from the organizer, refunds will be issued strictly to the original method of payment used at the time of purchase.
                </p>

                <h3 class="text-lg font-medium text-gray-900 mb-3">General Terms</h3>
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li><strong>Force Majeure:</strong> Noxxi shall not be held liable for cancellations, delays, or non-performance resulting from circumstances beyond its reasonable control, including but not limited to natural disasters, government restrictions, public health emergencies, or other unforeseen events.</li>
                    <li><strong>Transaction Fees:</strong> All refunds are subject to applicable transaction or processing fees, which will be deducted from the refund amount.</li>
                    <li><strong>Refund Timelines:</strong> Refunds may require up to 30 business days to process, depending on the payment channel and financial institution involved.</li>
                    <li><strong>Non-Transferability:</strong> Refunds will only be issued to the original payment method used at the time of purchase. Tickets obtained through resale or transfer are not eligible for refunds unless explicitly approved by Noxxi and the event organizer.</li>
                    <li><strong>Customer Obligations:</strong> Customers are responsible for providing accurate and up-to-date contact information to ensure successful delivery of tickets and communications.</li>
                    <li><strong>Governing Law:</strong> These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which the transaction occurs, subject to the exclusive jurisdiction of the competent courts in that territory.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">9. Contests & Promotions</h2>
                <p class="text-gray-700 mb-4">
                    From time to time, Noxxi may organize contests, sweepstakes, or promotional campaigns. Participation will be governed by separate terms and conditions applicable to each specific promotion.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">10. User Conduct</h2>
                <p class="text-gray-700 mb-4">Users of the Noxxi platform agree to:</p>
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Refrain from posting or transmitting defamatory, offensive, unlawful, or harmful content.</li>
                    <li>Avoid engaging in fraudulent, misleading, or deceptive practices.</li>
                    <li>Respect the intellectual property rights of Noxxi and third parties.</li>
                    <li>Not attempt to gain unauthorized access to restricted areas of the platform.</li>
                </ul>
                <p class="text-gray-700 mb-4">
                    Violations may result in suspension or permanent termination of platform access.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">11. Intellectual Property</h2>
                <p class="text-gray-700 mb-4">
                    All content on the Noxxi platform, including but not limited to text, graphics, logos, images, and software, is the exclusive property of Kollint Ventures Ltd. or its licensors. Unauthorized use, reproduction, distribution, or modification of this content is strictly prohibited.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">12. Privacy & Data Protection</h2>
                <p class="text-gray-700 mb-4">
                    Noxxi is committed to safeguarding user data and complies with applicable data protection and privacy laws in the regions where it operates. By using the platform, users consent to the collection, processing, and use of personal information in line with Noxxi's Privacy Policy.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">13. Limitation of Liability</h2>
                <p class="text-gray-700 mb-4">
                    To the fullest extent permitted by law, Noxxi shall not be held liable for indirect, incidental, consequential, or punitive damages, including but not limited to lost profits, lost business opportunities, or service interruptions.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">14. Governing Law</h2>
                <p class="text-gray-700 mb-4">
                    These Terms are governed by the laws of the applicable jurisdiction based on the location of the transaction. Any disputes will be subject to the exclusive jurisdiction of the courts within that region.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">15. Waiver</h2>
                <p class="text-gray-700 mb-4">
                    Failure by Noxxi to enforce any provision of these Terms does not constitute a waiver of its right to enforce the same or any other provision in the future.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">16. Acknowledgment</h2>
                <p class="text-gray-700 mb-4">
                    By accessing or using the Noxxi platform, you acknowledge that you have read, understood, and agreed to these Terms and Conditions.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">17. Contact Us</h2>
                <p class="text-gray-700 mb-4">For inquiries or support, please contact:</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700 mb-2"><strong>Email:</strong> info@noxxi.com</p>
                    <p class="text-gray-700"><strong>Address:</strong> Kollint Ventures Ltd., Kerugoya, Kirinyaga County (with service coverage extending internationally).</p>
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