<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2d3748">
    <title>{{ \App\Models\AppSetting::getAppName() }} - Reset Password</title>
    <meta name="description" content="Set new password - {{ \App\Models\AppSetting::getAppName() }} AI-Based Stock Analysis & Prediction System">
    <link rel="shortcut icon" href="{{ \App\Models\AppSetting::getLogoUrl() }}" type="image/x-icon">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Apply theme immediately before page renders -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            const darkMode = localStorage.getItem('darkMode');
            const isDark = theme === 'dark' || darkMode === 'true' ||
                          (!theme && !darkMode && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

<style>
    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-15px);
        }
    }

    @keyframes pulse-subtle {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.8;
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-pulse-subtle {
        animation: pulse-subtle 3s ease-in-out infinite;
    }

    .gradient-accent {
        background: #0078d7;
        color: white;
    }

    .gradient-accent:hover {
        background: #0066b8;
    }

    .dark .gradient-accent {
        background: #2563eb;
        color: white;
    }

    .dark .gradient-accent:hover {
        background: #1d4ed8;
    }
</style>
</head>

<body
    class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-50 font-sans antialiased">
    <!-- Top Navigation -->
    <nav
        class="fixed w-full top-0 z-40 backdrop-blur-md bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 ">
            <div class="flex justify-between items-center h-16">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                        <img class="w-8 h-8" src="{{ \App\Models\AppSetting::getLogoUrl() }}" alt="Logo">
                    </div>
                    <span
                        class="text-lg font-bold text-primary dark:text-primary-light">
                        {{ \App\Models\AppSetting::getAppName() }}
                    </span>
                </a>
                <div class="flex items-center gap-4">
                    <button id="theme-toggle"
                        class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        <i class="fa-solid fa-moon w-5 h-5 block dark:hidden text-slate-700"></i>
                        <i class="fa-solid fa-sun w-5 h-5 hidden dark:block text-slate-300"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen pt-24 pb-12 px-4 flex items-center justify-center">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold mb-2">Set New Password</h1>
                <p class="text-slate-600 dark:text-slate-400">Create a new password for your account</p>
            </div>

            <!-- Reset Form Card -->
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 shadow-lg hover:shadow-xl transition">

                <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Email Address
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                            autocomplete="username"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:focus:ring-blue-400 transition"
                            placeholder="you@example.com">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            New Password
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:focus:ring-blue-400 transition"
                            placeholder="••••••••">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Confirm Password
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:focus:ring-blue-400 transition"
                            placeholder="••••••••">
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full mt-6 px-6 py-3 rounded-lg gradient-accent font-semibold transition shadow-md hover:shadow-lg">
                        Reset Password
                    </button>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}"
                        class="text-sm text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-300 transition font-medium">
                        ← Back to Login
                    </a>
                </div>
            </div>

            <!-- Benefits Section -->
            <div class="mt-12 grid grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">AI-Powered</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">Real-Time Data</p>
                </div>
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">Secure & Safe</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Background Elements -->
    <div
        class="fixed top-20 right-10 w-32 h-32 bg-blue-200 dark:bg-blue-900/20 rounded-full opacity-20 animate-float pointer-events-none">
    </div>
    <div class="fixed bottom-20 left-10 w-40 h-40 bg-teal-200 dark:bg-teal-900/20 rounded-full opacity-20 animate-float"
        style="animation-delay: 2s;"></div>

    <!-- Theme Toggle Script -->
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
            document.documentElement.classList.toggle('dark', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            localStorage.setItem('darkMode', isDark ? 'true' : 'false');
        }

        const themeToggle = document.getElementById('theme-toggle');
        applyThemePreference(getStoredThemePreference());

        themeToggle.addEventListener('click', () => {
            applyThemePreference(!document.documentElement.classList.contains('dark'));
        });
    </script>
</body>

</html>
