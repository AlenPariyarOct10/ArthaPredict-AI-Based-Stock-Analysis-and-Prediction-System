<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2d3748">
    <title>{{ \App\Models\AppSetting::getAppName() }} - Create Account</title>
    <meta name="description" content="Create your {{ \App\Models\AppSetting::getAppName() }} account and start analyzing stocks with AI">
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

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .gradient-accent {
            background: #0078d7;
        }

        .dark .gradient-accent {
            background: #75b6e9;
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
                        class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        <i class="fa-solid fa-moon w-5 h-5 block dark:hidden text-slate-700"></i>
                        <i class="fa-solid fa-sun w-5 h-5 hidden dark:block text-slate-300"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen pt-24 pb-12 px-4  flex items-center justify-center">
        <div class="w-full max-w-md">
            <!-- Welcome Message -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold mb-2">Get Started</h1>
                <p class="text-slate-600 dark:text-slate-400">Create your ArthaPredict account and start analyzing
                    stocks with AI</p>
            </div>

            <!-- Register Form Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 shadow-lg hover:shadow-xl transition">
                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Registration Error</p>
                        <ul class="text-sm text-red-600 dark:text-red-300 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Full Name
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            autocomplete="name"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:focus:ring-blue-400 transition"
                            placeholder="John Doe">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Email Address
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            autocomplete="email"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:focus:ring-blue-400 transition"
                            placeholder="you@example.com">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Password
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
                        <label for="password_confirmation"
                            class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Confirm Password
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:focus:ring-blue-400 transition"
                            placeholder="••••••••">
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Terms Agreement -->
                    <div class="flex items-start pt-2">
                        <input id="agree" type="checkbox" name="agree"
                            class="mt-1 w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-500 focus:ring-blue-500 dark:focus:ring-blue-400 cursor-pointer">
                        <label for="agree" class="ms-3 text-sm text-slate-600 dark:text-slate-400 cursor-pointer">
                            I agree to the <a href="#"
                                class="text-blue-600 dark:text-blue-400 hover:underline">Terms of Service</a> and
                            <a href="#" class="text-blue-600 dark:text-blue-400 hover:underline">Privacy
                                Policy</a>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full mt-6 px-6 py-3 rounded-lg gradient-accent text-white font-semibold hover:opacity-90 transition shadow-md hover:shadow-lg">
                        Create Account
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-300 dark:border-slate-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400">Already have an
                            account?</span>
                    </div>
                </div>

                <!-- Login Link -->
                <a href="{{ route('login') }}"
                    class="w-full block text-center px-6 py-3 rounded-lg border-2 border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400 font-semibold hover:bg-blue-50 dark:hover:bg-blue-900/10 transition">
                    Sign In Instead
                </a>
            </div>

            <!-- Features -->
            <div class="mt-12 space-y-3 text-center text-sm text-slate-600 dark:text-slate-400">
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>AI-powered stock analysis</span>
                </div>
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>Real-time market data</span>
                </div>
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span>Secure and private</span>
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
