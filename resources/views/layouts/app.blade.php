<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html" class="no-transitions">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0078d7">

    <title>{{ \App\Models\AppSetting::getAppName() }}</title>
    <link rel="shortcut icon" href="{{ \App\Models\AppSetting::getLogoUrl() }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Scripts (Vite includes Tailwind & Alpine) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- External Libs -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        /* Custom Theme Colors */
        :root {
            --background: #f8fafc;
            /* slate-50 */
            --foreground: #0f172a;
            /* slate-900 */
            --card: #ffffff;
            --card-foreground: #0f172a;
            --muted: #f1f5f9;
            /* slate-100 */
            --muted-foreground: #64748b;
            /* slate-500 */
            --border: #e2e8f0;
            /* slate-200 */
            --secondary: #f1f5f9;
            /* slate-100 */
            --primary: #0078d7;
            /* sky-blue */
            --destructive: #ef4444;
            /* red-500 */

            /* Opacity variants */
            --background-60: rgba(248, 250, 252, 0.6);
            --muted-50: rgba(241, 245, 249, 0.5);
            --secondary-50: rgba(241, 245, 249, 0.5);
            --destructive-10: rgba(239, 68, 68, 0.1);
        }

        .dark {
            --background: #0f172a;
            /* slate-900 */
            --foreground: #f8fafc;
            /* slate-50 */
            --card: #1e293b;
            /* slate-800 */
            --card-foreground: #f8fafc;
            --muted: #1e293b;
            /* slate-800 */
            --muted-foreground: #94a3b8;
            /* slate-400 */
            --border: #334155;
            /* slate-700 */
            --secondary: #334155;
            /* slate-700 */
            --primary: #75b6e9;
            /* light-blue */
            --destructive: #f87171;
            /* red-400 */

            /* Opacity variants */
            --background-60: rgba(15, 23, 42, 0.6);
            --muted-50: rgba(30, 41, 59, 0.5);
            --secondary-50: rgba(51, 65, 85, 0.5);
            --destructive-10: rgba(248, 113, 113, 0.1);
        }

        /* Utility classes mapping to theme variables */
        .bg-background {
            background-color: var(--background) !important;
        }

        .text-foreground {
            color: var(--foreground) !important;
        }

        .bg-card {
            background-color: var(--card) !important;
        }

        .bg-muted {
            background-color: var(--muted) !important;
        }

        .text-muted-foreground {
            color: var(--muted-foreground) !important;
        }

        .border-border {
            border-color: var(--border) !important;
        }

        .bg-secondary {
            background-color: var(--secondary) !important;
        }

        .text-destructive {
            color: var(--destructive) !important;
        }

        .bg-input {
            background-color: var(--card) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .text-primary-foreground {
            color: #ffffff !important;
        }

        .bg-destructive {
            background-color: var(--destructive) !important;
        }

        .text-destructive-foreground {
            color: #ffffff !important;
        }

        .placeholder-muted-foreground::placeholder {
            color: var(--muted-foreground) !important;
        }

        /* Opacity & State Variants */
        .bg-background\/60 {
            background-color: var(--background-60) !important;
        }

        .bg-muted\/50 {
            background-color: var(--muted-50) !important;
        }

        .bg-secondary\/50 {
            background-color: var(--secondary-50) !important;
        }

        .hover\:bg-destructive\/10:hover {
            background-color: var(--destructive-10) !important;
        }

        .hover\:bg-muted:hover {
            background-color: var(--muted) !important;
        }

        .hover\:bg-primary\/80:hover {
            background-color: var(--primary) !important;
            opacity: 0.8;
        }

        .focus\:bg-primary\/80:focus {
            background-color: var(--primary) !important;
            opacity: 0.8;
        }

        .active\:bg-primary\/90:active {
            background-color: var(--primary) !important;
            opacity: 0.9;
        }

        .hover\:bg-destructive\/80:hover {
            background-color: var(--destructive) !important;
            opacity: 0.8;
        }

        .active\:bg-destructive\/90:active {
            background-color: var(--destructive) !important;
            opacity: 0.9;
        }

        .focus\:ring-primary:focus {
            --tw-ring-color: var(--primary) !important;
        }

        .focus\:border-primary:focus {
            border-color: var(--primary) !important;
        }

        .focus\:ring-destructive:focus {
            --tw-ring-color: var(--destructive) !important;
        }

        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Smooth theme transitions on everything */
        *,
        *::before,
        *::after {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, fill 0.2s ease, stroke 0.2s ease, box-shadow 0.2s ease;
        }

        /* Prevent transitions during initial page load */
        .no-transitions *,
        .no-transitions *::before,
        .no-transitions *::after {
            transition: none !important;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Gradient accent - solid blue, no gradient */
        .gradient-accent {
            background: #0078d7;
        }

        .dark .gradient-accent {
            background: #75b6e9;
        }

        /* Active nav item — solid blue bg with white text */
        .nav-active {
            background: #0078d7;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(0, 120, 215, 0.35);
        }

        .dark .nav-active {
            background: #75b6e9;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(117, 182, 233, 0.4);
        }

        /* Keep icon color white inside active link */
        .nav-active svg {
            stroke: #ffffff;
        }
    </style>
    <script>
        function getStoredThemePreference() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark' || theme === 'light') {
                return theme === 'dark';
            }

            const legacyValue = localStorage.getItem('darkMode');
            if (legacyValue === 'true' || legacyValue === 'false') {
                return legacyValue === 'true';
            }

            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function applyThemePreference(isDark) {
            const html = document.documentElement;
            html.classList.toggle('dark', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            localStorage.setItem('darkMode', isDark ? 'true' : 'false');
        }

        applyThemePreference(getStoredThemePreference());

        document.addEventListener("DOMContentLoaded", function () {
            document.documentElement.classList.remove("no-transitions");
        });
    </script>
</head>

<body class="font-sans antialiased text-foreground bg-background" x-data="{
    sidebarOpen: false,
    darkMode: getStoredThemePreference()
}" x-init="
    $watch('darkMode', function(val) {
        applyThemePreference(val);
    });

    // Sync dark mode state from HTML on page load
    darkMode = document.documentElement.classList.contains('dark');
">
    <div class="h-screen flex overflow-hidden">

        <!-- Sidebar -->
        <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="w-64 flex flex-col overflow-hidden lg:translate-x-0 lg:static transition duration-300 transform bg-card dark:bg-card border-r border-border dark:border-border">
            <!-- Logo / Header -->
            <div class="flex items-center justify-between p-6 border-b border-border dark:border-border">
                <div class="flex items-center gap-3">
                    <img class="w-8 h-8" src="{{ \App\Models\AppSetting::getLogoUrl() }}" alt="Logo">
                    <div>
                        <div
                            class="font-bold text-lg text-primary dark:text-primary-light">
                            {{ \App\Models\AppSetting::getAppName() }}
                        </div>
                        <div class="text-xs text-muted-foreground">Stock Analysis</div>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-muted-foreground hover:text-foreground">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Navigation - Scrollable -->
            <nav class="flex-1 overflow-y-auto py-4 space-y-2">
                <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('dashboard') ? 'nav-active' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100' }}"
                    href="{{ route('dashboard') }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('stocks.*') ? 'nav-active' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100' }}"
                    href="{{ route('stocks.index') }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span>Stocks</span>
                </a>

                <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('watchlist.*') ? 'nav-active' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100' }}"
                    href="{{ route('watchlist.index') }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 5a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"></path>
                    </svg>
                    <span>Watchlist</span>
                </a>

                <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('analysis.index') ? 'nav-active' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100' }}"
                    href="{{ route('analysis.index') }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                    <span>Analysis</span>
                </a>

                <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('arthanotes.*') ? 'nav-active' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100' }}"
                    href="{{ route('arthanotes.index') }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                    <span>ArthaNotes</span>
                </a>

                @if(Auth::user() && !Auth::user()->is_admin)
                    <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('feedback.*') ? 'nav-active' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-100' }}"
                        href="{{ route('feedback.index') }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4v-4z">
                            </path>
                        </svg>
                        <span>Feedback</span>
                    </a>
                @endif

                @if(Auth::user() && Auth::user()->is_admin)
                    <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('admin.feedbacks.*') ? 'nav-active' : 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30' }}"
                        href="{{ route('admin.feedbacks.index') }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8h2a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2h2m10 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7">
                            </path>
                        </svg>
                        <span>Client Feedback</span>
                    </a>

                    <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('admin.dataset-import.*') ? 'nav-active' : 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30' }}"
                        href="{{ route('admin.dataset-import.index') }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Dataset Import</span>
                    </a>

                    <a class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium {{ request()->routeIs('admin.dashboard') ? 'nav-active' : 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30' }}"
                        href="{{ route('admin.dashboard') }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                            </path>
                        </svg>
                        <span>Admin Panel</span>
                    </a>
                @endif
            </nav>

            <!-- Sidebar Footer - Fixed at bottom -->
            <div class="p-4 border-t border-border dark:border-border bg-card dark:bg-card">
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition font-medium text-muted-foreground hover:bg-muted dark:hover:bg-secondary">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-card dark:bg-card border-b border-border dark:border-border">
                <div class="flex justify-between items-center py-4 px-6">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = true"
                            class="lg:hidden text-muted-foreground hover:text-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-6">
                        <!-- Theme Toggle -->
                        <button @click="darkMode = !darkMode"
                            class="p-2 rounded-lg bg-muted dark:bg-secondary hover:bg-muted/80 dark:hover:bg-secondary/80 transition text-foreground">
                            <svg x-show="!darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                                </path>
                            </svg>
                            <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </button>

                        @auth
                            <!-- User Dropdown -->
                            <div x-data="{ dropdownOpen: false }" class="relative">
                                <button @click="dropdownOpen = !dropdownOpen"
                                    class="flex items-center gap-3 focus:outline-none hover:opacity-80 transition">
                                    <div class="flex flex-col text-right">
                                        <div class="text-sm font-semibold text-foreground">{{ Auth::user()->name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ Auth::user()->email }}</div>
                                    </div>
                                    @if (Auth::user()->profile_image_url)
                                        <img src="{{ Auth::user()->profile_image_url }}"
                                            class="w-10 h-10 rounded-full object-cover" alt="Profile Image">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full gradient-accent flex items-center justify-center text-white font-bold text-sm">
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        </div>
                                    @endif
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="dropdownOpen" @click.away="dropdownOpen = false"
                                    class="absolute right-0 mt-2 w-56 bg-card dark:bg-card rounded-lg shadow-lg z-20 border border-border dark:border-border overflow-hidden"
                                    x-cloak>
                                    <div
                                        class="px-4 py-3 border-b border-border dark:border-border bg-muted/50 dark:bg-secondary/50">
                                        <div class="font-medium text-foreground">{{ Auth::user()->name }}</div>
                                        <div class="text-sm text-muted-foreground truncate">{{ Auth::user()->email }}</div>
                                    </div>
                                    <a href="{{ route('profile.edit') }}"
                                        class="block px-4 py-2 text-sm text-foreground hover:bg-muted dark:hover:bg-secondary transition">
                                        Profile Settings
                                    </a>
                                    <form method="POST"
                                        action="{{ Auth::user()->is_admin ? route('admin.logout') : route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="block w-full text-left px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition font-medium">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-hidden">
                <div class="h-full overflow-y-auto overflow-x-hidden bg-background dark:bg-background">
                <div class="container mx-auto px-4 md:px-6 py-8">
                    @isset($header)
                        <div class="mb-0">
                            <div
                                class="bg-card dark:bg-card rounded-xl shadow-sm border border-border dark:border-border p-6">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    @yield('content')
                    {{ $slot ?? '' }}
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
