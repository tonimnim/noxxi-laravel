<!-- Static Header for Legal Pages -->
<header class="fixed left-0 right-0 z-50 bg-white shadow-lg">
    <div class="w-full px-4 md:px-6 lg:px-12">
        <div class="flex items-center justify-between h-16 md:h-20">
            <!-- Logo -->
            <div class="flex items-center lg:ml-[180px] xl:ml-[210px]">
                <a href="/" class="logo-briski text-2xl md:text-3xl tracking-tight hover:opacity-80 transition-opacity text-[#223338]">
                    NOXXI
                </a>
            </div>

            <!-- Center Navigation - Desktop Only -->
            <nav class="hidden lg:flex items-center justify-center absolute left-1/2 transform -translate-x-1/2">
                <div class="flex items-center gap-6 xl:gap-10">
                    <a href="/explore" class="text-sm font-medium hover:opacity-80 transition-opacity text-[#223338]">
                        Explore
                    </a>
                    <a href="/sell-tickets" class="text-sm font-medium hover:opacity-80 transition-opacity text-[#223338]">
                        Sell tickets
                    </a>
                    <a href="/enterprise" class="text-sm font-medium hover:opacity-80 transition-opacity text-[#223338]">
                        Enterprise
                    </a>
                    <a href="/help" class="text-sm font-medium hover:opacity-80 transition-opacity text-[#223338]">
                        Help
                    </a>
                </div>
            </nav>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-2 md:gap-4 lg:mr-12 xl:mr-20">
                <!-- Auth Section -->
                @guest
                    <div class="hidden sm:block">
                        <a href="/login" class="text-sm font-medium hover:opacity-80 transition-opacity text-[#223338]">
                            Sign in
                        </a>
                    </div>
                @else
                    <div class="hidden sm:flex items-center gap-3">
                        <a href="/account" class="flex items-center gap-2 hover:opacity-80 transition-opacity text-[#223338]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="text-sm font-medium">My Account</span>
                        </a>
                        <form method="POST" action="/logout" class="inline">
                            @csrf
                            <button type="submit" class="text-sm hover:opacity-80 transition-opacity text-[#223338]">
                                Logout
                            </button>
                        </form>
                    </div>
                @endguest

                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="lg:hidden p-1.5 sm:p-2 rounded-md hover:opacity-80 text-[#223338]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden lg:hidden bg-white shadow-lg">
        <div class="px-6 pt-2 pb-3 space-y-1">
            <a href="/explore" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                Explore
            </a>
            <a href="/sell-tickets" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                Sell tickets
            </a>
            <a href="/enterprise" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                Enterprise
            </a>
            <a href="/help" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                Help
            </a>
        </div>
        <div class="pt-4 pb-3 border-t border-gray-200">
            <div class="px-6 space-y-2">
                @guest
                    <a href="/login" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        Sign in
                    </a>
                @else
                    <a href="/account" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                        My Account
                    </a>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                            Logout
                        </button>
                    </form>
                @endguest
            </div>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
    } else {
        menu.classList.add('hidden');
    }
}
</script>