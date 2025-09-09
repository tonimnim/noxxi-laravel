<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Privacy Policy - Noxxi</title>

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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Noxxi Privacy Policy</h1>
            <p class="text-gray-600">Last Updated: 30th September 2025</p>
        </div>

        <!-- Content -->
        <div class="prose max-w-none">
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                <p class="text-gray-700 mb-4">
                    Kollint Ventures Ltd. t/a Noxxi ("Noxxi") is a Private Limited Liability Company registered in Kenya with offices at Kerugoya, Kirinyaga County. Email: 
                    <a href="mailto:info@noxxi.com" class="text-indigo-600 hover:text-indigo-800">info@noxxi.com</a>.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Privacy Commitment</h2>
                <p class="text-gray-700 mb-4">
                    Noxxi is dedicated to safeguarding your privacy and protecting any personal information you share with us. We are transparent about what information we collect and how we use it. Your details will only be used for the purposes outlined in this Policy—primarily to deliver the services you request and to improve your overall experience with us.
                </p>
                <p class="text-gray-700 mb-4">
                    We may also use the information we collect to better understand user preferences and improve our services. Important service-related notifications, such as booking confirmations, event updates, or travel changes, may still be sent to you as part of fulfilling our contractual obligations.
                </p>
                <p class="text-gray-700 mb-4">
                    Noxxi takes appropriate measures to secure your information and will always respect your data rights. For any questions, you may contact our Data Protection Officer (see Section 11).
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Scope of Policy</h2>
                <p class="text-gray-700 mb-4">
                    This Privacy Policy applies to all individuals interacting with Noxxi, including customers, website visitors, app users, and others using our products or services. Updates may be made from time to time to reflect operational, legal, or regulatory changes. We recommend reviewing this Policy periodically.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Personal Information We Collect</h2>
                <p class="text-gray-700 mb-4">Noxxi may collect the following categories of personal information:</p>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Personal Details:</h3>
                        <p class="text-gray-700">Name, preferred name, and photo.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Demographic Information:</h3>
                        <p class="text-gray-700">Gender, date of birth, nationality, passport or ID details, and language preferences.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Contact Information:</h3>
                        <p class="text-gray-700">Address, phone number, email, and messaging profiles.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Consent Records:</h3>
                        <p class="text-gray-700">Records of permissions or consents provided.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Purchase Information:</h3>
                        <p class="text-gray-700">Past purchases, bookings, and feedback.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Information:</h3>
                        <p class="text-gray-700">Payment methods, card details, billing address, and transaction history.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Technical Information:</h3>
                        <p class="text-gray-700">Device type, IP address, browser data, and login activity.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Professional Information:</h3>
                        <p class="text-gray-700">Employer or organization details (when interacting in a professional capacity).</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Event/Travel Details:</h3>
                        <p class="text-gray-700">Bookings, itineraries, and preferences.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Feedback:</h3>
                        <p class="text-gray-700">Reviews, surveys, or customer support communications.</p>
                    </div>
                </div>

                <p class="text-gray-700 mt-4">
                    If you provide personal information about others (e.g., when booking on behalf of another person), you confirm that you have their consent to share it.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Purpose of Processing & Legal Basis</h2>
                <p class="text-gray-700 mb-4">We process your personal information for purposes including:</p>
                
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li><strong>Service Delivery:</strong> To provide access to our platforms, products, and services (necessary for contract fulfillment).</li>
                    <li><strong>Booking Management:</strong> To process and manage bookings.</li>
                    <li><strong>Compliance & Verification:</strong> To meet legal and regulatory requirements.</li>
                    <li><strong>Platform Operations:</strong> To operate and improve our websites, apps, and services.</li>
                    <li><strong>IT & Security Management:</strong> To maintain secure and reliable systems.</li>
                    <li><strong>Financial Management:</strong> To manage payments, audits, and vendor relationships.</li>
                    <li><strong>Legal Purposes:</strong> To establish, defend, or exercise legal claims.</li>
                    <li><strong>Service Improvements:</strong> To enhance user experience and resolve issues.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Sharing of Personal Information</h2>
                <p class="text-gray-700 mb-4">Noxxi may share information in the following circumstances:</p>
                
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Within Noxxi and its affiliates to support operations.</li>
                    <li>With legal/regulatory authorities as required by law.</li>
                    <li>With professional advisors (e.g., auditors, lawyers) under confidentiality.</li>
                    <li>With third-party providers (e.g., payment processors, service partners) necessary for delivering services.</li>
                    <li>With law enforcement for crime prevention or fraud investigations.</li>
                    <li>With business successors in case of merger, acquisition, or asset transfer.</li>
                </ul>
                
                <p class="text-gray-700 mb-4">
                    We take reasonable measures to protect your information but cannot guarantee absolute security during transmission.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Protecting Your Information</h2>
                <p class="text-gray-700 mb-4">
                    We implement technical and organizational safeguards to protect your personal data against unauthorized access, alteration, or disclosure. However, no method of internet transmission is 100% secure, so sharing information online is at your own risk.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Accuracy of Your Information</h2>
                <p class="text-gray-700 mb-4">
                    We strive to maintain accurate and up-to-date records. If inaccuracies are identified, we will update or delete the data as appropriate. We may occasionally request confirmation of your details.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">9. Data Minimization</h2>
                <p class="text-gray-700 mb-4">
                    We only collect the personal information necessary for the purposes stated in this Policy and retain it only for as long as required by law, ongoing services, or potential legal claims.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">10. Data Retention</h2>
                <p class="text-gray-700 mb-4">Noxxi retains personal data only for as long as necessary to:</p>
                
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Fulfill the purposes outlined in this Policy, or</li>
                    <li>Meet legal, regulatory, or contractual requirements.</li>
                </ul>
                
                <p class="text-gray-700 mb-4">
                    When data is no longer needed, it is securely deleted or anonymized.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">11. Your Rights</h2>
                <p class="text-gray-700 mb-4">Depending on applicable data protection laws, you may have the right to:</p>
                
                <ul class="text-gray-700 mb-4 list-disc pl-6 space-y-2">
                    <li>Access, correct, or delete your personal information.</li>
                    <li>Restrict or object to certain types of processing.</li>
                    <li>Withdraw consent where processing is based on consent.</li>
                    <li>Request data portability.</li>
                </ul>
                
                <p class="text-gray-700 mb-4">
                    To exercise your rights, please contact: 
                    <a href="mailto:info@noxxi.com" class="text-indigo-600 hover:text-indigo-800">info@noxxi.com</a>.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">12. Governing Law</h2>
                <p class="text-gray-700 mb-4">
                    This Privacy Policy will be governed by and interpreted in accordance with the laws of the jurisdiction where the transaction occurs, subject to the authority of the courts in that jurisdiction.
                </p>
            </section>

            <!-- Contact Information -->
            <section class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Us</h2>
                <p class="text-gray-700 mb-4">For any privacy-related inquiries or to exercise your data rights, please contact:</p>
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